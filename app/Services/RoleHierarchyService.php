<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class RoleHierarchyService
{
    public function getAllChildren(Role $role): Collection
    {
        $children = collect();

        foreach ($role->childRoles as $child) {
            $children->push($child);
            $children = $children->merge($this->getAllChildren($child));
        }

        return $children;
    }

    public function userHasPermission(User $user, string $permission): bool
    {
        foreach ($user->permissions as $permission) {
            if ($permission->name === $permission) {
                return true;
            }
        }

        foreach ($user->roles as $role) {
            if ($role->hasPermissionTo($permission)) {
                return true;
            }

            foreach ($this->getAllChildren($role) as $child) {
                if ($child->hasPermissionTo($permission)) {
                    return true;
                }
            }
        }

        return false;
    }
}
