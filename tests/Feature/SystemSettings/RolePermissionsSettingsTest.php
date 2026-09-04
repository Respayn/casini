<?php

namespace Tests\Feature\SystemSettings;

use App\Enums\PermissionGroup;
use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\Repositories\RoleRepository;
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

    public function test_settings_page_includes_new_system_settings_sections_in_order(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $admin = collect($roles)->firstWhere('systemName', RoleEnum::ADMIN->value);

        $groupNames = collect($admin['permissions'])->pluck('name')->values();

        $agencyIndex = $groupNames->search(PermissionGroup::SYSTEM_SETTINGS->value);
        $dictionariesIndex = $groupNames->search(PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES->value);
        $usersIndex = $groupNames->search(PermissionGroup::SYSTEM_SETTINGS_USERS->value);
        $rolesIndex = $groupNames->search(PermissionGroup::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS->value);

        $this->assertNotFalse($agencyIndex);
        $this->assertNotFalse($dictionariesIndex);
        $this->assertNotFalse($usersIndex);
        $this->assertNotFalse($rolesIndex);

        $this->assertSame(
            PermissionGroup::SYSTEM_SETTINGS->label(),
            collect($admin['permissions'])->firstWhere('name', PermissionGroup::SYSTEM_SETTINGS->value)['displayName']
        );
        $this->assertLessThan($dictionariesIndex, $agencyIndex);
        $this->assertLessThan($usersIndex, $dictionariesIndex);
        $this->assertLessThan($rolesIndex, $usersIndex);

        $clientsIndex = $groupNames->search(PermissionGroup::CLIENTS_AND_PROJECTS->value);
        $this->assertNotFalse($clientsIndex);
        $this->assertLessThan($clientsIndex, $rolesIndex);

        $dictionaries = collect($admin['permissions'])
            ->firstWhere('name', PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES->value);
        $this->assertFalse($dictionaries['isSecondary']);
    }

    public function test_save_strips_locked_full_access_for_new_settings_sections(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::MANAGER->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                if ($permission['name'] === PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES->value) {
                    $permission['haveFullAccess'] = true;
                    $permission['canRead'] = true;
                    $permission['canEdit'] = true;
                }

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $this->assertFalse($manager->hasPermissionTo('full system settings dictionaries'));
        $this->assertTrue($manager->hasPermissionTo('edit system settings dictionaries'));
        $this->assertTrue($manager->hasPermissionTo('read system settings dictionaries'));
    }

    public function test_save_strips_locked_full_access_for_clients_and_projects_parent(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::MANAGER->value) {
                return $role;
            }

            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                if ($permission['name'] === PermissionGroup::CLIENTS_AND_PROJECTS->value) {
                    $permission['haveFullAccess'] = true;
                    $permission['canRead'] = true;
                    $permission['canEdit'] = true;
                }

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $this->assertFalse($manager->hasPermissionTo('full clients and projects'));
        $this->assertTrue($manager->hasPermissionTo('edit clients and projects'));
        $this->assertTrue($manager->hasPermissionTo('read clients and projects'));
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

        app(RoleRepository::class)->deleteRole($admin->id);
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

    public function test_delete_role_throws_for_default(): void
    {
        $default = Role::findByName(RoleEnum::DEFAULT->value);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('permissions.default_role_undeletable'));

        app(RoleRepository::class)->deleteRole($default->id);
    }

    public function test_default_role_cannot_be_deleted_when_missing_from_payload(): void
    {
        $default = Role::findByName(RoleEnum::DEFAULT->value);
        $roles = collect($this->roleService->getRolesAndPermissionsForSettingsPage())
            ->reject(fn (array $role) => $role['systemName'] === RoleEnum::DEFAULT->value)
            ->values()
            ->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull(Role::findById($default->id));
    }

    public function test_default_role_save_always_restores_granted_reads(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::DEFAULT->value) {
                return $role;
            }

            $role['name'] = 'Hacked Name';
            $role['useInProjectFilter'] = true;
            $role['useInManagersList'] = true;
            $role['hasChildRoles'] = true;
            $role['permissions'] = collect($role['permissions'])->map(function (array $permission) {
                $permission['canRead'] = false;
                $permission['canEdit'] = true;
                $permission['haveFullAccess'] = true;

                return $permission;
            })->all();

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $default = Role::findByName(RoleEnum::DEFAULT->value);
        $default->refresh();

        $this->assertSame(RoleEnum::DEFAULT->label(), $default->display_name);
        $this->assertFalse((bool) $default->use_in_project_filter);
        $this->assertFalse((bool) $default->use_in_managers_list);
        $this->assertTrue($default->hasPermissionTo('read channels'));
        $this->assertTrue($default->hasPermissionTo('read statistics'));
        $this->assertTrue($default->hasPermissionTo('read reports'));
        $this->assertTrue($default->hasPermissionTo('read planning'));
        $this->assertTrue($default->hasPermissionTo('read clients and projects self'));
        $this->assertFalse($default->hasPermissionTo('edit channels'));
        $this->assertFalse($default->hasPermissionTo('full channels'));
        $this->assertFalse($default->hasPermissionTo('read system settings'));
    }

    public function test_default_role_loads_with_seeded_permissions(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $default = collect($roles)->firstWhere('systemName', RoleEnum::DEFAULT->value);

        $this->assertNotNull($default);

        $channels = collect($default['permissions'])->firstWhere('name', PermissionGroup::CHANNELS->value);
        $self = collect($default['permissions'])->firstWhere('name', PermissionGroup::CLIENTS_AND_PROJECTS_SELF->value);
        $agency = collect($default['permissions'])->firstWhere('name', PermissionGroup::SYSTEM_SETTINGS->value);

        $this->assertTrue($channels['canRead']);
        $this->assertFalse($channels['canEdit']);
        $this->assertTrue($self['canRead']);
        $this->assertFalse($agency['canRead']);
    }

    public function test_admin_role_save_preserves_display_name(): void
    {
        $roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
        $roles = collect($roles)->map(function (array $role) {
            if ($role['systemName'] !== RoleEnum::ADMIN->value) {
                return $role;
            }

            $role['name'] = 'Hacked Admin';

            return $role;
        })->all();

        $result = $this->roleService->saveChanges($roles);

        $this->assertTrue($result->isSuccess());

        $admin = Role::findByName(RoleEnum::ADMIN->value);
        $this->assertSame(RoleEnum::ADMIN->label(), $admin->display_name);
    }
}
