<?php

namespace Tests\Feature\SystemSettings;

use App\Enums\PermissionGroup;
use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\Services\RoleService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RolePermissionsSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private RoleService $roleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->roleService = app(RoleService::class);
    }

    public function test_admin_role_loads_with_all_permissions_enabled(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $admin = collect($roles)->firstWhere('systemName', RoleEnum::ADMIN->value);

        $this->assertNotNull($admin);

        foreach ($admin['permissions'] as $permission) {
            $this->assertTrue($permission['canRead'], $permission['name']);
            $this->assertTrue($permission['canEdit'], $permission['name']);
            $this->assertTrue($permission['haveFullAccess'], $permission['name']);
        }
    }

    public function test_settings_page_hides_media_planning_group(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $admin = collect($roles)->firstWhere('systemName', RoleEnum::ADMIN->value);

        $groupNames = collect($admin['permissions'])->pluck('name');

        $this->assertFalse($groupNames->contains(PermissionGroup::MEDIA_PLANNING->value));

        $defaults = $this->roleService->getPermissionsWithDefaultValuesForSettingsPage();
        $defaultNames = collect($defaults)->pluck('name');

        $this->assertFalse($defaultNames->contains(PermissionGroup::MEDIA_PLANNING->value));
    }

    public function test_save_strips_locked_full_access_for_non_admin(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::MANAGER->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                if ($permission['name'] === PermissionGroup::CHANNELS->value) {
                    $permission['haveFullAccess'] = true;
                    $permission['canRead'] = true;
                }

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $this->assertFalse($manager->hasPermissionTo('full channels'));
        $this->assertTrue($manager->hasPermissionTo('read channels'));
    }

    public function test_save_strips_locked_edit_access_for_non_admin(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::MANAGER->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                if ($permission['name'] === PermissionGroup::STATISTICS->value) {
                    $permission['canEdit'] = true;
                    $permission['canRead'] = true;
                }

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $this->assertFalse($manager->hasPermissionTo('edit statistics'));
        $this->assertTrue($manager->hasPermissionTo('read statistics'));
    }

    public function test_admin_role_cannot_be_deleted_when_missing_from_payload(): void
    {
        $admin = Role::findByName(RoleEnum::ADMIN->value);
        $roles = collect($this->roleService->getRolesAndPermissionsForSettingsPage())
            ->reject(fn (array $role) => $role['systemName'] === RoleEnum::ADMIN->value)
            ->values()
            ->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull(Role::findById($admin->id));
    }

    public function test_save_restores_all_permissions_for_admin(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::ADMIN->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                $permission['canRead'] = false;
                $permission['canEdit'] = false;
                $permission['haveFullAccess'] = false;

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $admin = Role::findByName(RoleEnum::ADMIN->value);
        $this->assertTrue($admin->hasPermissionTo('read channels'));
        $this->assertTrue($admin->hasPermissionTo('edit channels'));
        $this->assertTrue($admin->hasPermissionTo('full channels'));
        $this->assertTrue($admin->hasPermissionTo('full system settings'));
    }

    public function test_save_preserves_hidden_media_planning_permissions(): void
    {
        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $manager->givePermissionTo('read media planning');
        $manager->givePermissionTo('edit media planning');

        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::MANAGER->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                if ($permission['name'] === PermissionGroup::CHANNELS->value) {
                    $permission['canRead'] = true;
                }

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $manager->refresh();
        $this->assertTrue($manager->hasPermissionTo('read media planning'));
        $this->assertTrue($manager->hasPermissionTo('edit media planning'));
        $this->assertTrue($manager->hasPermissionTo('read channels'));
    }

    public function test_delete_role_throws_for_admin(): void
    {
        $admin = Role::findByName(RoleEnum::ADMIN->value);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Нельзя удалить роль администратор');

        app(\App\Repositories\RoleRepository::class)->deleteRole($admin->id);
    }

    public function test_load_normalizes_read_and_edit_when_only_full_in_db(): void
    {
        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $manager->syncPermissions(['full report templates']);

        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $role = collect($roles)->firstWhere('systemName', RoleEnum::MANAGER->value);
        $permission = collect($role['permissions'])
            ->firstWhere('name', PermissionGroup::REPORT_TEMPLATES->value);

        $this->assertTrue($permission['haveFullAccess']);
        $this->assertTrue($permission['canEdit']);
        $this->assertTrue($permission['canRead']);
    }

    public function test_load_normalizes_read_when_only_edit_in_db(): void
    {
        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $manager->syncPermissions(['edit planning']);

        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $role = collect($roles)->firstWhere('systemName', RoleEnum::MANAGER->value);
        $permission = collect($role['permissions'])
            ->firstWhere('name', PermissionGroup::PLANNING->value);

        $this->assertFalse($permission['haveFullAccess']);
        $this->assertTrue($permission['canEdit']);
        $this->assertTrue($permission['canRead']);
    }

    public function test_save_propagates_full_access_to_read_and_edit(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::MANAGER->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                if ($permission['name'] === PermissionGroup::REPORT_TEMPLATES->value) {
                    $permission['haveFullAccess'] = true;
                    $permission['canEdit'] = false;
                    $permission['canRead'] = false;
                }

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $this->assertTrue($manager->hasPermissionTo('full report templates'));
        $this->assertTrue($manager->hasPermissionTo('edit report templates'));
        $this->assertTrue($manager->hasPermissionTo('read report templates'));
    }

    public function test_save_propagates_edit_to_read(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::MANAGER->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                if ($permission['name'] === PermissionGroup::PLANNING->value) {
                    $permission['haveFullAccess'] = false;
                    $permission['canEdit'] = true;
                    $permission['canRead'] = false;
                }

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $this->assertFalse($manager->hasPermissionTo('full planning'));
        $this->assertTrue($manager->hasPermissionTo('edit planning'));
        $this->assertTrue($manager->hasPermissionTo('read planning'));
    }
}
