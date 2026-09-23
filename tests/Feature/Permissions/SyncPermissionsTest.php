<?php

namespace Tests\Feature\Permissions;

use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\User;
use App\Services\Permissions\PermissionSynchronizer;
use App\Support\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

class SyncPermissionsTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    public function test_first_sync_creates_the_roles_and_all_manifest_permissions(): void
    {
        $report = app(PermissionSynchronizer::class)->sync();

        $this->assertCount(6, $report['roles_created']);
        $this->assertCount(count(app(ModuleRegistry::class)->names()), $report['created']);
        $this->assertSame(RoleName::values(), Role::orderBy('id')->pluck('name')->all());
        $this->assertSame(0, Permission::custom()->count());
        $this->assertSame(Permission::count(), Permission::manifest()->count());
    }

    public function test_sync_is_idempotent(): void
    {
        $sync = app(PermissionSynchronizer::class);
        $sync->sync();
        $logs = ActivityLog::count();

        $second = $sync->sync();

        $this->assertSame([], $second['created']);
        $this->assertSame([], $second['adopted']);
        $this->assertSame([], $second['roles']);
        $this->assertSame([], $second['roles_created']);
        $this->assertSame($logs, ActivityLog::count(), 'Une synchronisation sans changement n\'écrit rien dans l\'audit.');
    }

    public function test_role_defaults_follow_the_manifests(): void
    {
        $this->syncPermissions();

        $admin = Role::findByName('admin_national', 'web')->permissions->pluck('name')->sort()->values()->all();
        $this->assertSame([
            'commissariats.create', 'commissariats.update', 'commissariats.view', 'informations.view',
            'mairies.create', 'mairies.update', 'mairies.view',
            'users.activate', 'users.create', 'users.reset_password', 'users.revoke_access', 'users.update', 'users.view',
        ], $admin);

        $commissaire = Role::findByName('commissaire', 'web')->permissions->pluck('name')->sort()->values()->all();
        $this->assertSame([
            'declarations.create', 'declarations.delete', 'declarations.update', 'declarations.view',
            'informations.create', 'informations.delete', 'informations.update', 'informations.view',
            'motos-retrouvees.create', 'motos-retrouvees.update', 'motos-retrouvees.view',
            'motos.create', 'motos.delete', 'motos.update', 'motos.view',
            'proprietaires.create', 'proprietaires.delete', 'proprietaires.update', 'proprietaires.view',
            'users.activate', 'users.create', 'users.reset_password', 'users.revoke_access', 'users.update', 'users.view',
        ], $commissaire);

        $mairie = Role::findByName('mairie', 'web')->permissions->pluck('name')->sort()->values()->all();
        $this->assertSame(['informations.view'], $mairie);

        foreach (['police', 'population', 'superadmin'] as $role) {
            $this->assertCount(0, Role::findByName($role, 'web')->permissions, "Le rôle $role n'a aucun défaut au socle.");
        }
    }

    public function test_admin_national_never_receives_technical_or_reserved_permissions(): void
    {
        $this->syncPermissions();
        $admin = User::factory()->adminNational()->create();

        foreach (['system.view', 'system.maintain', 'roles.view', 'permissions.assign', 'permissions.create', 'permissions.view', 'audit.view', 'users.delete'] as $ability) {
            $this->assertFalse($admin->can($ability), "admin_national ne doit pas avoir $ability");
        }
        $this->assertTrue($admin->can('users.create'));
    }

    public function test_superadmin_passes_through_gate_before_without_any_attached_permission(): void
    {
        $this->syncPermissions();
        $superadmin = $this->superadmin();

        $this->assertCount(0, $superadmin->getAllPermissions());
        foreach (['system.view', 'roles.view', 'permissions.assign', 'audit.view', 'anything.at_all'] as $ability) {
            $this->assertTrue($superadmin->can($ability));
        }
    }

    public function test_sync_never_touches_custom_permissions_nor_their_role_assignments(): void
    {
        $this->syncPermissions();
        $custom = Permission::create(['name' => 'rapports.view', 'guard_name' => 'web', 'source' => Permission::SOURCE_CUSTOM]);
        $role = Role::findByName('commissaire', 'web');
        $role->givePermissionTo($custom);

        $this->syncPermissions();

        $this->assertTrue(Permission::where('name', 'rapports.view')->exists());
        $this->assertSame('custom', Permission::where('name', 'rapports.view')->value('source'));
        $this->assertTrue($role->fresh()->hasPermissionTo('rapports.view'), 'L\'attribution d\'une permission custom à un rôle survit à la synchronisation.');
    }

    public function test_sync_restores_manifest_defaults_but_keeps_direct_user_exceptions(): void
    {
        $this->syncPermissions();
        $role = Role::findByName('commissaire', 'web');
        $role->revokePermissionTo('users.view');
        $police = User::factory()->police()->create();
        $police->givePermissionTo('users.delete');

        $report = app(PermissionSynchronizer::class)->sync();

        $this->assertSame(['users.view'], $report['roles']['commissaire']['added']);
        $this->assertTrue($role->fresh()->hasPermissionTo('users.view'));
        $this->assertTrue($police->fresh()->hasDirectPermission('users.delete'), 'Les exceptions directes des utilisateurs ne sont jamais touchées.');
    }

    public function test_a_custom_permission_later_declared_by_a_manifest_is_adopted(): void
    {
        $this->syncPermissions();
        Permission::create(['name' => 'demo.view', 'guard_name' => 'web', 'source' => Permission::SOURCE_CUSTOM]);
        config(['modules.demo' => ['label' => 'Démo', 'permissions' => ['view' => 'Voir'], 'roles' => []]]);

        $report = app(PermissionSynchronizer::class)->sync();

        $this->assertSame(['demo.view'], $report['adopted']);
        $this->assertSame('manifest', Permission::where('name', 'demo.view')->value('source'));
    }

    public function test_orphan_manifest_permissions_are_reported_and_never_deleted(): void
    {
        $this->syncPermissions();
        Permission::create(['name' => 'ancien.view', 'guard_name' => 'web', 'source' => Permission::SOURCE_MANIFEST]);

        $report = app(PermissionSynchronizer::class)->sync();

        $this->assertSame(['ancien.view'], $report['orphans']);
        $this->assertTrue(Permission::where('name', 'ancien.view')->exists());
    }

    public function test_a_reserved_permission_cannot_be_offered_to_a_non_superadmin_role(): void
    {
        config(['modules.audit.roles' => ['admin_national' => ['defaults' => ['view'], 'optional' => []]]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('audit.view');
        app(ModuleRegistry::class)->assertValid();
    }

    public function test_the_shipped_manifests_are_valid(): void
    {
        app(ModuleRegistry::class)->assertValid();

        $this->assertTrue(app(ModuleRegistry::class)->isReserved('system.maintain'));
        $this->assertTrue(app(ModuleRegistry::class)->isReserved('permissions.create'));
        $this->assertFalse(app(ModuleRegistry::class)->isReserved('users.delete'));
    }
}
