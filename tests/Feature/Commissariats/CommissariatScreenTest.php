<?php

namespace Tests\Feature\Commissariats;

use App\Models\ActivityLog;
use App\Models\Commissariat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Commissariats (W1) : liste, création et modification en modale, suppression en soft delete.
 * Désactiver un commissariat coupe immédiatement l'accès de ses utilisateurs (ARCHITECTURE §11).
 */
class CommissariatScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $superadmin;

    private User $adminNational;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->superadmin = $this->superadmin();
        $this->adminNational = User::factory()->adminNational()->create();
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/commissariats')->assertRedirect('/login');

        $this->actingAs(User::factory()->commissaire()->create())->get('/commissariats')->assertForbidden();
        $this->actingAs($this->adminNational)->get('/commissariats')->assertOk()->assertSee('COMMISSARIATS');
        $this->actingAs($this->superadmin)->get('/commissariats')->assertOk();
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs($this->adminNational)->get('/commissariats')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_admin_national_creates_a_commissariat(): void
    {
        $this->actingAs($this->adminNational)
            ->post('/commissariats', ['name' => 'Commissariat du 3e Arrondissement', 'code' => 'BKO-03'])
            ->assertRedirect('/commissariats')
            ->assertSessionHas('status');

        $commissariat = Commissariat::where('name', 'Commissariat du 3e Arrondissement')->firstOrFail();
        $this->assertSame('BKO-03', $commissariat->code);
        $this->assertTrue($commissariat->is_active);
        $this->assertSame(1, ActivityLog::where('action', 'commissariats.created')->count());
    }

    public function test_creation_is_forbidden_without_the_permission(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->post('/commissariats', ['name' => 'Commissariat interdit'])->assertForbidden();
        $this->assertFalse(Commissariat::where('name', 'Commissariat interdit')->exists());
    }

    public function test_duplicate_name_or_code_is_refused_with_a_message(): void
    {
        Commissariat::factory()->create(['name' => 'Commissariat de Kati', 'code' => 'BKO-01']);

        $this->actingAs($this->adminNational)->from('/commissariats')
            ->post('/commissariats', ['name' => 'Commissariat de Kati'])
            ->assertRedirect('/commissariats')
            ->assertSessionHasErrors('name');

        $this->actingAs($this->adminNational)->from('/commissariats')
            ->post('/commissariats', ['name' => 'Un autre nom', 'code' => 'BKO-01'])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_national_updates_a_commissariat(): void
    {
        $commissariat = Commissariat::factory()->create(['name' => 'Ancien nom']);

        $this->actingAs($this->adminNational)
            ->put("/commissariats/{$commissariat->id}", ['name' => 'Nouveau nom', 'code' => null, 'is_active' => '1'])
            ->assertRedirect('/commissariats');

        $commissariat->refresh();
        $this->assertSame('Nouveau nom', $commissariat->name);
        $this->assertSame(1, ActivityLog::where('action', 'commissariats.updated')->count());
    }

    public function test_deactivating_a_commissariat_revokes_the_access_of_its_users(): void
    {
        $commissariat = Commissariat::factory()->create(['is_active' => true]);
        $police = User::factory()->police($commissariat)->create();

        DB::table('sessions')->insert(['id' => 'sess-1', 'user_id' => $police->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);
        $police->tokens()->create(['name' => 'téléphone', 'token' => hash('sha256', 'jeton-test'), 'abilities' => ['*']]);

        $this->actingAs($this->adminNational)
            ->put("/commissariats/{$commissariat->id}", ['name' => $commissariat->name, 'is_active' => '0'])
            ->assertRedirect('/commissariats');

        $this->assertFalse($commissariat->fresh()->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $police->id)->count());
        $this->assertSame(0, $police->tokens()->count());
        $this->assertFalse($police->fresh()->canAuthenticate());
    }

    public function test_reactivating_does_not_touch_sessions_or_tokens(): void
    {
        $commissariat = Commissariat::factory()->create(['is_active' => false]);
        $police = User::factory()->police($commissariat)->create();
        DB::table('sessions')->insert(['id' => 'sess-2', 'user_id' => $police->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);

        $this->actingAs($this->adminNational)
            ->put("/commissariats/{$commissariat->id}", ['name' => $commissariat->name, 'is_active' => '1'])
            ->assertRedirect('/commissariats');

        $this->assertTrue($commissariat->fresh()->is_active);
        $this->assertSame(1, DB::table('sessions')->where('user_id', $police->id)->count());
    }

    public function test_deletion_is_reserved_to_the_superadmin_by_default(): void
    {
        $commissariat = Commissariat::factory()->create();

        $this->actingAs($this->adminNational)->delete("/commissariats/{$commissariat->id}")->assertForbidden();
        $this->assertNotSoftDeleted($commissariat);

        $this->actingAs($this->superadmin)->delete("/commissariats/{$commissariat->id}")->assertRedirect('/commissariats');
        $this->assertSoftDeleted($commissariat);
        $this->assertSame(1, ActivityLog::where('action', 'commissariats.deleted')->count());
    }

    public function test_admin_national_deletes_once_granted_the_exception(): void
    {
        $commissariat = Commissariat::factory()->create();
        $this->adminNational->givePermissionTo('commissariats.delete');

        $this->actingAs($this->adminNational)->delete("/commissariats/{$commissariat->id}")->assertRedirect('/commissariats');

        $this->assertSoftDeleted($commissariat);
    }

    public function test_a_commissariat_with_users_cannot_be_deleted(): void
    {
        $commissariat = Commissariat::factory()->create();
        User::factory()->police($commissariat)->create();

        $this->actingAs($this->superadmin)->delete("/commissariats/{$commissariat->id}")->assertForbidden();
        $this->assertNotSoftDeleted($commissariat);
    }

    public function test_the_deactivated_institution_locks_its_users_out_immediately(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();

        $this->actingAs($chef)->get('/')->assertOk();

        $this->actingAs($this->adminNational)->put("/commissariats/{$commissariat->id}", ['name' => $commissariat->name, 'is_active' => '0']);

        // Nouvelle instance : actingAs() réutilise l'objet PHP passé, dont la relation commissariat resterait en
        // cache avec l'état actif d'avant la désactivation (un vrai utilisateur, lui, revient toujours en base).
        $this->actingAs($chef->fresh())->get('/')->assertRedirect('/login');
    }

    public function test_the_list_offers_status_chips(): void
    {
        $this->actingAs($this->adminNational)->get('/commissariats')->assertOk()
            ->assertSee('commissariats-filter', false)
            ->assertSee('data-token="etat-inactif"', false);
    }
}
