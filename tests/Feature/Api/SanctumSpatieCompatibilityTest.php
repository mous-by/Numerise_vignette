<?php

namespace Tests\Feature\Api;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Risque signalé par ARCHITECTURE §14 : les rôles Spatie vivent sur le guard `web`, mais les requêtes API passent par
 * `auth:sanctum`. Les permissions doivent s'évaluer correctement dans ce contexte.
 */
class SanctumSpatieCompatibilityTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();

        // Routes de sonde : le socle n'a pas encore d'endpoint API protégé par permission.
        Route::middleware(['api', 'auth:sanctum', 'permission:probe.view'])->get('api/v1/_probe_custom', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', 'permission:users.view'])->get('api/v1/_probe_role', fn () => response()->json(['ok' => true]));
    }

    private function get_(string $uri, User $user)
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this->withToken($user->createToken('test')->plainTextToken)->getJson($uri);
    }

    public function test_without_the_permission_a_bearer_request_gets_a_403_json(): void
    {
        Permission::create(['name' => 'probe.view', 'guard_name' => 'web', 'source' => 'custom']);
        $police = User::factory()->police()->create();

        $this->get_('/api/v1/_probe_custom', $police)->assertForbidden()->assertJsonStructure(['message']);
    }

    public function test_a_direct_permission_is_honoured_under_the_sanctum_guard(): void
    {
        Permission::create(['name' => 'probe.view', 'guard_name' => 'web', 'source' => 'custom']);
        $police = User::factory()->police()->create();
        $police->givePermissionTo('probe.view');

        $this->get_('/api/v1/_probe_custom', $police)->assertOk()->assertJson(['ok' => true]);
        $this->assertTrue($police->fresh()->can('probe.view'));
    }

    public function test_a_permission_inherited_from_the_role_is_honoured(): void
    {
        $this->get_('/api/v1/_probe_role', User::factory()->commissaire()->create())->assertOk();
        $this->get_('/api/v1/_probe_role', User::factory()->police()->create())->assertForbidden();
    }

    public function test_the_superadmin_bypass_works_under_sanctum(): void
    {
        $this->get_('/api/v1/_probe_role', User::factory()->superadmin()->create())->assertOk();
    }

    public function test_the_permission_cache_does_not_leak_between_requests(): void
    {
        Permission::create(['name' => 'probe.view', 'guard_name' => 'web', 'source' => 'custom']);
        $police = User::factory()->police()->create();
        $this->get_('/api/v1/_probe_custom', $police)->assertForbidden();

        $police->givePermissionTo('probe.view');
        $this->get_('/api/v1/_probe_custom', $police)->assertOk();

        $police->revokePermissionTo('probe.view');
        $this->get_('/api/v1/_probe_custom', $police)->assertForbidden();
    }

    public function test_roles_are_resolved_through_the_morph_alias_for_tokens_and_roles(): void
    {
        $police = User::factory()->police()->create();
        $police->createToken('t');

        $this->assertSame('user', \DB::table('personal_access_tokens')->value('tokenable_type'));
        $this->assertSame('user', \DB::table('model_has_roles')->where('model_id', $police->id)->value('model_type'));
        $this->assertTrue($police->hasRole('police'));
    }
}
