<?php

namespace Tests\Feature\Mairies;

use App\Models\ActivityLog;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Mairies (W2), même patron que Commissariats (W1) : liste, création et modification en modale,
 * suppression en soft delete. Désactiver une mairie coupe immédiatement l'accès de ses utilisateurs.
 */
class MairieScreenTest extends TestCase
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
        $this->get('/mairies')->assertRedirect('/login');

        $this->actingAs(User::factory()->commissaire()->create())->get('/mairies')->assertForbidden();
        $this->actingAs($this->adminNational)->get('/mairies')->assertOk()->assertSee('MAIRIES');
        $this->actingAs($this->superadmin)->get('/mairies')->assertOk();
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs($this->adminNational)->get('/mairies')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_admin_national_creates_a_mairie(): void
    {
        $this->actingAs($this->adminNational)
            ->post('/mairies', ['name' => 'Mairie de la Commune III', 'code' => 'CIII'])
            ->assertRedirect('/mairies')
            ->assertSessionHas('status');

        $mairie = Mairie::where('name', 'Mairie de la Commune III')->firstOrFail();
        $this->assertSame('CIII', $mairie->code);
        $this->assertTrue($mairie->is_active);
        $this->assertSame(1, ActivityLog::where('action', 'mairies.created')->count());
    }

    public function test_creation_is_forbidden_without_the_permission(): void
    {
        $agent = User::factory()->mairie()->create();

        $this->actingAs($agent)->post('/mairies', ['name' => 'Mairie interdite'])->assertForbidden();
        $this->assertFalse(Mairie::where('name', 'Mairie interdite')->exists());
    }

    public function test_duplicate_name_or_code_is_refused_with_a_message(): void
    {
        Mairie::factory()->create(['name' => 'Mairie de Kati', 'code' => 'KATI']);

        $this->actingAs($this->adminNational)->from('/mairies')
            ->post('/mairies', ['name' => 'Mairie de Kati'])
            ->assertRedirect('/mairies')
            ->assertSessionHasErrors('name');

        $this->actingAs($this->adminNational)->from('/mairies')
            ->post('/mairies', ['name' => 'Un autre nom', 'code' => 'KATI'])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_national_updates_a_mairie(): void
    {
        $mairie = Mairie::factory()->create(['name' => 'Ancien nom']);

        $this->actingAs($this->adminNational)
            ->put("/mairies/{$mairie->id}", ['name' => 'Nouveau nom', 'code' => null, 'is_active' => '1'])
            ->assertRedirect('/mairies');

        $mairie->refresh();
        $this->assertSame('Nouveau nom', $mairie->name);
        $this->assertSame(1, ActivityLog::where('action', 'mairies.updated')->count());
    }

    public function test_deactivating_a_mairie_revokes_the_access_of_its_users(): void
    {
        $mairie = Mairie::factory()->create(['is_active' => true]);
        $agent = User::factory()->mairie($mairie)->create();

        DB::table('sessions')->insert(['id' => 'sess-mairie-1', 'user_id' => $agent->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);
        $agent->tokens()->create(['name' => 'poste', 'token' => hash('sha256', 'jeton-test-mairie'), 'abilities' => ['*']]);

        $this->actingAs($this->adminNational)
            ->put("/mairies/{$mairie->id}", ['name' => $mairie->name, 'is_active' => '0'])
            ->assertRedirect('/mairies');

        $this->assertFalse($mairie->fresh()->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $agent->id)->count());
        $this->assertSame(0, $agent->tokens()->count());
        $this->assertFalse($agent->fresh()->canAuthenticate());
    }

    public function test_deletion_is_reserved_to_the_superadmin_by_default(): void
    {
        $mairie = Mairie::factory()->create();

        $this->actingAs($this->adminNational)->delete("/mairies/{$mairie->id}")->assertForbidden();
        $this->assertNotSoftDeleted($mairie);

        $this->actingAs($this->superadmin)->delete("/mairies/{$mairie->id}")->assertRedirect('/mairies');
        $this->assertSoftDeleted($mairie);
        $this->assertSame(1, ActivityLog::where('action', 'mairies.deleted')->count());
    }

    public function test_a_mairie_with_users_cannot_be_deleted_even_by_the_superadmin(): void
    {
        $mairie = Mairie::factory()->create();
        User::factory()->mairie($mairie)->create();

        $this->actingAs($this->superadmin)->delete("/mairies/{$mairie->id}")->assertForbidden();
        $this->assertNotSoftDeleted($mairie);
    }

    public function test_the_deactivated_institution_locks_its_users_out_immediately(): void
    {
        $mairie = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairie)->create();

        $this->actingAs($agent)->get('/')->assertOk();

        $this->actingAs($this->adminNational)->put("/mairies/{$mairie->id}", ['name' => $mairie->name, 'is_active' => '0']);

        // Nouvelle instance : actingAs() réutilise l'objet PHP passé, dont la relation mairie resterait en
        // cache avec l'état actif d'avant la désactivation.
        $this->actingAs($agent->fresh())->get('/')->assertRedirect('/login');
    }

    public function test_the_list_offers_status_chips(): void
    {
        $this->actingAs($this->adminNational)->get('/mairies')->assertOk()
            ->assertSee('mairies-filter', false)
            ->assertSee('data-token="etat-inactif"', false);
    }
}
