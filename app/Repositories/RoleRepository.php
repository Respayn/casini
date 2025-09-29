<?php

namespace App\Repositories;

use App\Data\PermissionData;
use App\Data\RoleData;
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
            $role->syncPermissions($permissions);

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

    public function deleteRole(int $roleId)
    {
        $role = Role::findById($roleId);
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
}
