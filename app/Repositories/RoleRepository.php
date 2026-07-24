<?php

namespace App\Repositories;

use App\Data\PermissionData;
use App\Data\RoleData;
use App\Enums\PermissionGroup;
use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\OperationResult;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RoleRepository
{
    public function getRoles()
    {
        $roles = Role::withCount('users')->get();

        return $roles->map(function ($role) {
            return new RoleData(
                $role->id,
                $role->name,
                $role->display_name,
                new Collection(),
                $role->use_in_project_filter,
                $role->use_in_managers_list,
                $role->use_in_specialist_list,
                new Collection(),
                ($role->users_count ?? 0) > 0
            );
        });
    }

    public function getRolesWithPermissions()
    {
        $roles = Role::with(['permissions', 'childRoles'])
            ->withCount('users')
            ->get();

        return $roles->map(function ($role) {
            return new RoleData(
                $role->id,
                $role->name,
                $role->display_name,
                collect(PermissionData::collect($role->permissions)),
                $role->use_in_project_filter,
                $role->use_in_managers_list,
                $role->use_in_specialist_list,
                $role->childRoles,
                ($role->users_count ?? 0) > 0
            );
        });
    }

    public function getPermissions()
    {
        $permissions = Permission::all();
        return $permissions->map(function ($permission) {
            return new PermissionData(
                $permission->id,
                $permission->name,
                $permission->group
            );
        });
    }

    public function createRole(array $roleData): OperationResult
    {
        try {
            $role = Role::create([
                'name' => Str::transliterate($roleData['name']),
                'display_name' => $roleData['name'],
                'use_in_project_filter' => $roleData['useInProjectFilter'],
                'use_in_managers_list' => $roleData['useInManagersList'],
                'use_in_specialist_list' => $roleData['useInSpecialistList'],
            ]);

            foreach ($roleData['permissions'] as $permission) {
                if ($permission['canRead']) {
                    $role->givePermissionTo('read ' . $permission['name']);
                }

                if ($permission['canEdit']) {
                    $role->givePermissionTo('edit ' . $permission['name']);
                }

                if ($permission['haveFullAccess']) {
                    $role->givePermissionTo('full ' . $permission['name']);
                }
            }

            $this->revokeDirectProductPermissionsForRoleUsers($role->fresh(['users']));

            if ($roleData['hasChildRoles']) {
                $childIds = array_filter(array_column($roleData['childRoles'], 'id'));
                $role->childRoles()->sync($childIds);
            } else {
                $role->childRoles()->detach();
            }

            return OperationResult::success();
        } catch (Exception $e) {
            return OperationResult::failure($e);
        }
    }

    public function updateRole(array $roleData): OperationResult
    {
        try {
            $role = Role::findById($roleData['id']);
            $role->display_name = $roleData['name'];
            $role->use_in_project_filter = $roleData['useInProjectFilter'];
            $role->use_in_managers_list = $roleData['useInManagersList'];
            $role->use_in_specialist_list = $roleData['useInSpecialistList'];
            $role->save();

            $permissions = [];
            foreach ($roleData['permissions'] as $permission) {
                if ($permission['canRead']) {
                    $permissions[] = 'read ' . $permission['name'];
                }

                if ($permission['canEdit']) {
                    $permissions[] = 'edit ' . $permission['name'];
                }

                if ($permission['haveFullAccess']) {
                    $permissions[] = 'full ' . $permission['name'];
                }
            }

            $permissions = array_values(array_unique(array_merge(
                $permissions,
                $this->hiddenGroupPermissionNames($role)
            )));

            $role->syncPermissions($permissions);
            $this->revokeDirectProductPermissionsForRoleUsers($role);

            if ($roleData['hasChildRoles']) {
                $childIds = array_filter(array_column($roleData['childRoles'], 'id'));
                $role->childRoles()->sync($childIds);
            } else {
                $role->childRoles()->detach();
            }

            return OperationResult::success();
        } catch (Exception $e) {
            return OperationResult::failure($e);
        }
    }

    public function deleteRole(int $roleId): void
    {
        $role = Role::findById($roleId);

        if ($role->name === RoleEnum::ADMIN->value) {
            throw new Exception('Нельзя удалить роль администратор');
        }

        $role->syncPermissions([]);
        $role->delete();
    }

    public function getRolesForFilter(): Collection
    {
        $roles = Role::where('use_in_project_filter', '=', true)
            ->withCount('users')
            ->get();

        return $roles->map(function ($role) {
            return new RoleData(
                $role->id,
                $role->name,
                $role->display_name,
                new Collection(),
                $role->use_in_project_filter,
                $role->use_in_managers_list,
                $role->use_in_specialist_list,
                new Collection(),
                ($role->users_count ?? 0) > 0
            );
        });
    }

    /**
     * Права скрытых на UI групп (например media planning), которые нельзя затирать при sync.
     *
     * @return list<string>
     */
    private function hiddenGroupPermissionNames(Role $role): array
    {
        $hiddenGroups = PermissionGroup::hiddenOnSettingsPageGroupNames();

        return $role->permissions
            ->filter(fn ($permission) => in_array($permission->group, $hiddenGroups, true))
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Права продуктов задаются через роли. Прямые назначения на пользователя
     * иначе переживают снятие галочки в «Продукты и права».
     */
    private function revokeDirectProductPermissionsForRoleUsers(Role $role): void
    {
        $productGroups = PermissionGroup::flatValues();
        $role->loadMissing('users');

        foreach ($role->users as $user) {
            $directProductPermissions = $user->getDirectPermissions()
                ->filter(fn (Permission $permission) => in_array($permission->group, $productGroups, true));

            if ($directProductPermissions->isEmpty()) {
                continue;
            }

            $user->revokePermissionTo($directProductPermissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
