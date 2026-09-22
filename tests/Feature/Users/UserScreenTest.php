<?php

namespace Tests\Feature\Users;

use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\Commissariat;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Utilisateurs (W5) : liste cloisonnée (User::visibleTo), création (mot de passe temporaire affiché une
 * fois), modification, activation, réinitialisation, révocation, suppression — plafond de rôle partout (D9).
 */
class UserScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->superadmin = $this->superadmin();
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/users')->assertRedirect('/login');
        $this->actingAs(User::factory()->mairie()->create())->get('/users')->assertForbidden();
        $this->actingAs($this->superadmin)->get('/users')->assertOk()->assertSee('UTILISATEURS');
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs($this->superadmin)->get('/users')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_a_commissaire_only_sees_the_police_of_his_own_commissariat(): void
    {
        $commissariat = Commissariat::factory()->create();
        $other = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create(['name' => 'Chef Visible']);
        $ownPolice = User::factory()->police($commissariat)->create(['name' => 'Police Visible']);
        $otherPolice = User::factory()->police($other)->create(['name' => 'Police Invisible']);

        // « Chef Visible » n'est pas cherché dans la page : son propre nom apparaît de toute façon dans la barre du haut.
        $page = $this->actingAs($chef)->get('/users')->assertOk();
        $page->assertSee('Police Visible')->assertDontSee('Police Invisible');
        $this->assertSame([$ownPolice->id], User::query()->visibleTo($chef)->pluck('id')->all(), 'Le commissaire ne voit que la police de son commissariat, jamais lui-même.');
    }

    public function test_an_admin_national_sees_everyone_but_superadmins(): void
    {
        $peer = User::factory()->adminNational()->create(['name' => 'Pair Admin']);
        User::factory()->commissaire()->create(['name' => 'Un Commissaire']);

        $page = $this->actingAs(User::factory()->adminNational()->create())->get('/users')->assertOk();
        $page->assertSee('Pair Admin')->assertSee('Un Commissaire')->assertDontSee($this->superadmin->name);
    }

    public function test_a_commissaire_creates_only_police_forced_into_his_own_commissariat(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();
        $other = Commissariat::factory()->create();

        $this->actingAs($chef)
            ->post('/users', ['name' => 'Agent Test', 'phone' => '70 00 00 30', 'role' => 'police', 'commissariat_id' => $other->id])
            ->assertRedirect('/users')
            ->assertSessionHas('temporary_password');

        $agent = User::where('phone', '+22370000030')->firstOrFail();
        $this->assertSame(RoleName::Police, $agent->roleName());
        $this->assertSame($commissariat->id, $agent->commissariat_id, 'Toujours son propre commissariat, quoi qu\'il soumette.');
    }

    public function test_an_admin_national_creates_a_mairie_agent_with_a_mairie(): void
    {
        $mairie = Mairie::factory()->create();
        $admin = User::factory()->adminNational()->create();

        $this->actingAs($admin)
            ->post('/users', ['name' => 'Agent Mairie', 'phone' => '70 00 00 40', 'role' => 'mairie', 'mairie_id' => $mairie->id])
            ->assertRedirect('/users');

        $agent = User::where('phone', '+22370000040')->firstOrFail();
        $this->assertSame(RoleName::Mairie, $agent->roleName());
        $this->assertSame($mairie->id, $agent->mairie_id);
    }

    public function test_a_commissaire_cannot_create_a_mairie_agent_through_a_forged_role(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->post('/users', ['name' => 'X', 'phone' => '70 00 00 31', 'role' => 'mairie'])
            ->assertSessionHasErrors('role');

        $this->assertFalse(User::where('phone', '+22370000031')->exists());
    }

    public function test_an_admin_national_creates_a_commissaire_with_an_institution(): void
    {
        $commissariat = Commissariat::factory()->create();
        $admin = User::factory()->adminNational()->create();

        $this->actingAs($admin)
            ->post('/users', ['name' => 'Nouveau Chef', 'phone' => '70 00 00 32', 'role' => 'commissaire', 'commissariat_id' => $commissariat->id])
            ->assertRedirect('/users')
            ->assertSessionHas('temporary_password_for', 'Nouveau Chef');

        $user = User::where('phone', '+22370000032')->firstOrFail();
        $this->assertSame($commissariat->id, $user->commissariat_id);
        $this->assertSame(1, ActivityLog::where('action', 'users.created')->where('subject_id', $user->id)->count());
    }

    public function test_an_admin_national_cannot_create_an_admin_national_or_a_superadmin(): void
    {
        $admin = User::factory()->adminNational()->create();

        foreach (['admin_national', 'superadmin'] as $role) {
            $this->actingAs($admin)->post('/users', ['name' => 'Z', 'phone' => '70 00 00 3'.($role === 'superadmin' ? 3 : 4), 'role' => $role])
                ->assertSessionHasErrors('role');
        }
    }

    public function test_updating_name_and_phone(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();
        $police = User::factory()->police($commissariat)->create(['name' => 'Ancien Nom']);

        $this->actingAs($chef)
            ->put("/users/{$police->id}", ['name' => 'Nouveau Nom', 'phone' => $police->phone])
            ->assertRedirect('/users');

        $this->assertSame('Nouveau Nom', $police->fresh()->name);
    }

    public function test_a_commissaire_cannot_change_the_institution_even_by_forging_the_field(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();
        $police = User::factory()->police($commissariat)->create();
        $other = Commissariat::factory()->create();

        $this->actingAs($chef)->put("/users/{$police->id}", [
            'name' => $police->name, 'phone' => $police->phone, 'commissariat_id' => $other->id,
        ])->assertRedirect('/users');

        $this->assertSame($commissariat->id, $police->fresh()->commissariat_id, 'Champ ignoré : réservé à admin national et superadmin.');
    }

    public function test_an_admin_national_changes_the_institution(): void
    {
        $police = User::factory()->police()->create();
        $newCommissariat = Commissariat::factory()->create();
        $admin = User::factory()->adminNational()->create();

        $this->actingAs($admin)->put("/users/{$police->id}", [
            'name' => $police->name, 'phone' => $police->phone, 'commissariat_id' => $newCommissariat->id,
        ])->assertRedirect('/users');

        $this->assertSame($newCommissariat->id, $police->fresh()->commissariat_id);
    }

    public function test_an_admin_national_cannot_manage_a_peer_admin_national(): void
    {
        $admin = User::factory()->adminNational()->create();
        $peer = User::factory()->adminNational()->create(['name' => 'Pair']);

        $this->actingAs($admin)->put("/users/{$peer->id}", ['name' => 'Changé', 'phone' => $peer->phone])->assertForbidden();
        $this->actingAs($admin)->delete("/users/{$peer->id}")->assertForbidden();
        $this->assertSame('Pair', $peer->fresh()->name);
    }

    public function test_deactivating_revokes_sessions_and_tokens(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();
        $police = User::factory()->police($commissariat)->create();
        DB::table('sessions')->insert(['id' => 'sess-u1', 'user_id' => $police->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);
        $police->tokens()->create(['name' => 'tel', 'token' => hash('sha256', 'jeton-user-test'), 'abilities' => ['*']]);

        $this->actingAs($chef)->put("/users/{$police->id}", [
            'name' => $police->name, 'phone' => $police->phone, 'is_active' => '0',
        ])->assertRedirect('/users');

        $this->assertFalse($police->fresh()->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $police->id)->count());
        $this->assertSame(0, $police->tokens()->count());
    }

    public function test_resetting_the_password_shows_it_once(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();
        $police = User::factory()->police($commissariat)->create();

        $this->actingAs($chef)->post("/users/{$police->id}/reset-password")
            ->assertRedirect('/users')
            ->assertSessionHas('temporary_password');

        $this->assertTrue($police->fresh()->must_change_password);
        $this->assertSame(1, ActivityLog::where('action', 'auth.password_reset')->count());
    }

    public function test_revoking_access_clears_sessions_and_tokens_without_deactivating(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();
        $police = User::factory()->police($commissariat)->create();
        DB::table('sessions')->insert(['id' => 'sess-u2', 'user_id' => $police->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()]);

        $this->actingAs($chef)->post("/users/{$police->id}/revoke-access")->assertRedirect('/users');

        $this->assertTrue($police->fresh()->is_active, 'Révoquer ne désactive pas le compte.');
        $this->assertSame(0, DB::table('sessions')->where('user_id', $police->id)->count());
    }

    public function test_deleting_a_user_is_a_soft_delete_and_revokes_access(): void
    {
        $commissariat = Commissariat::factory()->create();
        $chef = User::factory()->commissaire($commissariat)->create();
        $chef->givePermissionTo('users.delete');
        $police = User::factory()->police($commissariat)->create();
        $police->tokens()->create(['name' => 'tel', 'token' => hash('sha256', 'jeton-delete-test'), 'abilities' => ['*']]);

        $this->actingAs($chef)->delete("/users/{$police->id}")->assertRedirect('/users');

        $this->assertSoftDeleted($police);
        $this->assertSame(0, $police->tokens()->count());
    }

    public function test_write_actions_are_forbidden_without_their_specific_permission(): void
    {
        // L'agent de mairie n'a aucun défaut sur le module users (config/modules/users.php) : jamais accordée,
        // pas une permission de rôle qu'on tenterait (en vain, §4.4) de retirer à un seul utilisateur.
        $agent = User::factory()->mairie()->create();
        $police = User::factory()->police()->create();

        $this->actingAs($agent)->post("/users/{$police->id}/reset-password")->assertForbidden();
    }

    public function test_the_last_active_superadmin_cannot_be_deactivated_or_deleted_from_the_screen(): void
    {
        $this->actingAs($this->superadmin)
            ->put("/users/{$this->superadmin->id}", ['name' => $this->superadmin->name, 'phone' => $this->superadmin->phone, 'is_active' => '0'])
            ->assertRedirect('/users')
            ->assertSessionHas('error');

        $this->assertTrue($this->superadmin->fresh()->is_active);

        $this->actingAs($this->superadmin)->delete("/users/{$this->superadmin->id}")
            ->assertRedirect('/users')
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($this->superadmin);
    }
}
