<?php

namespace App\Support;

use App\Enums\PermissionGroup;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SystemSettingsSectionPermissions
{
    /**
     * @return list<string>
     */
    public static function readPermissionNames(PermissionGroup $group): array
    {
        $name = $group->value;

        return [
            'read '.$name,
            'edit '.$name,
            'full '.$name,
        ];
    }

    /**
     * @return list<string>
     */
    public static function editPermissionNames(PermissionGroup $group): array
    {
        $name = $group->value;

        return [
            'edit '.$name,
            'full '.$name,
        ];
    }

    public static function middleware(PermissionGroup $group): string
    {
        return 'permission:'.implode('|', self::readPermissionNames($group));
    }

    public static function userCanRead(PermissionGroup $group, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyPermission(self::readPermissionNames($group));
    }

    public static function userCanEdit(PermissionGroup $group, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyPermission(self::editPermissionNames($group));
    }

    public static function agency(): PermissionGroup
    {
        return PermissionGroup::SYSTEM_SETTINGS;
    }

    public static function dictionaries(): PermissionGroup
    {
        return PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES;
    }

    public static function users(): PermissionGroup
    {
        return PermissionGroup::SYSTEM_SETTINGS_USERS;
    }

    public static function rolesAndPermissions(): PermissionGroup
    {
        return PermissionGroup::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS;
    }
}
