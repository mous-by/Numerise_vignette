<?php

namespace Tests\Feature\Permissions;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\User;
use App\Services\Permissions\PermissionManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Voie B (D23) : permissions créées et attribuées depuis l'interface, réservées au superadmin.
 */
class PermissionManagerTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private PermissionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->manager = app(PermissionManager::class);
    }

    public function test_the_superadmin_creates_a_custom_permission_and_it_is_audited(): void
    {
        $superadmin = $this->superadmin();
        $this->actingAs($superadmin); // le journal fige l'auteur connecté

        $permission = $this->manager->createCustom($superadmin, 'rapports.export');

        $this->assertSame('custom', $permission->source);
        $log = ActivityLog::where('action', 'permissions.created')->latest('id')->first();
        $this->assertSame('rapports.export', $log->subject_label);
        $this->assertSame('superadmin', $log->user_role);
    }

    public function test_only_the_superadmin_can_create_permissions(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->manager->createCustom(User::factory()->adminNational()->create(), 'rapports.export');
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_refused(string $name): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->createCustom($this->superadmin(), $name);
    }

    public static function invalidNames(): array
    {
        return [
            'sans point' => ['rapports'],
            'majuscules' => ['Rapports.View'],
            'espaces' => ['rapports. view'],
            'trois segments' => ['a.b.c'],
            'vide' => [''],
            'module réservé system' => ['system.reboot'],
            'module réservé audit' => ['audit.export'],
            'module réservé permissions' => ['permissions.delete_all'],
            'module réservé roles' => ['roles.edit'],
            'trop long' => [str_repeat('a', 150).'.view'],
        ];
    }

    public function test_a_name_already_declared_by_a_manifest_or_created_is_refused(): void
    {
        $superadmin = $this->superadmin();
        $this->manager->createCustom($superadmin, 'rapports.export');

        foreach (['users.view', 'rapports.export'] as $name) {
            try {
                $this->manager->createCustom($superadmin, $name);
                $this->fail("$name aurait dû être refusée");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('name', $e->errors());
            }
        }
    }

    public function test_a_custom_permission_can_be_given_to_a_user_as_an_exception(): void
    {
        $superadmin = $this->superadmin();
        $this->manager->createCustom($superadmin, 'rapports.export');
        $police = User::factory()->police()->create();

        $result = $this->manager->syncUserPermissions($superadmin, $police, ['rapports.export']);

        $this->assertSame(['rapports.export'], $result['added']);
        $this->assertTrue($police->fresh()->can('rapports.export'));
        $this->assertSame(1, ActivityLog::where('action', 'permissions.user_synced')->count());

        $this->manager->syncUserPermissions($superadmin, $police->fresh(), []);
        $this->assertFalse($police->fresh()->can('rapports.export'));
    }

    public function test_the_manifest_optional_permissions_of_the_role_are_in_the_pool_but_never_reserved_ones(): void
    {
        $superadmin = $this->superadmin();
        $commissaire = User::factory()->commissaire()->create();

        $pool = $this->manager->poolFor($commissaire);

        $this->assertSame(['users.delete'], $pool['assignable']->pluck('name')->all());
        $this->assertContains('users.view', $pool['inherited']->pluck('name')->all());

        $this->expectException(ValidationException::class);
        $this->manager->syncUserPermissions($superadmin, $commissaire, ['system.view']);
    }

    public function test_a_permission_outside_the_pool_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->syncUserPermissions($this->superadmin(), User::factory()->police()->create(), ['users.delete']);
    }

    public function test_the_superadmin_target_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->syncUserPermissions($this->superadmin(), $this->superadmin(), []);
    }

    public function test_a_non_superadmin_cannot_assign_permissions(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->manager->syncUserPermissions(User::factory()->adminNational()->create(), User::factory()->police()->create(), []);
    }

    public function test_custom_permissions_can_be_attached_to_a_role_and_manifest_ones_stay_untouched(): void
    {
        $superadmin = $this->superadmin();
        $this->manager->createCustom($superadmin, 'rapports.export');
        $role = Role::findByName('commissaire', 'web');

        $this->manager->syncRoleCustomPermissions($superadmin, $role, ['rapports.export']);

        $names = $role->fresh()->permissions->pluck('name');
        $this->assertTrue($names->contains('rapports.export'));
        $this->assertTrue($names->contains('users.view'), 'Les défauts du manifeste restent en place.');
        $this->syncPermissions();
        $this->assertTrue($role->fresh()->hasPermissionTo('rapports.export'));

        $this->expectException(ValidationException::class);
        $this->manager->syncRoleCustomPermissions($superadmin, $role, ['users.delete']); // une permission de manifeste ne s'attribue pas ici
    }

    public function test_the_superadmin_role_takes_no_attachments(): void
    {
        $this->expectException(ValidationException::class);
        $this->manager->syncRoleCustomPermissions($this->superadmin(), Role::findByName('superadmin', 'web'), []);
    }

    public function test_only_custom_permissions_can_be_deleted(): void
    {
        $superadmin = $this->superadmin();
        $custom = $this->manager->createCustom($superadmin, 'rapports.export');

        $this->manager->deleteCustom($superadmin, $custom);
        $this->assertFalse(Permission::where('name', 'rapports.export')->exists());
        $this->assertSame(1, ActivityLog::where('action', 'permissions.deleted')->count());

        $this->expectException(ValidationException::class);
        $this->manager->deleteCustom($superadmin, Permission::where('name', 'users.view')->first());
    }
}
