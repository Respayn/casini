<?php

namespace App\Support;

use App\Enums\PermissionGroup;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class ClientsAndProjectsPermissions
{
    /**
     * @return list<PermissionGroup>
     */
    public static function accessGroups(): array
    {
        return [
            PermissionGroup::CLIENTS_AND_PROJECTS,
            PermissionGroup::CLIENTS_AND_PROJECTS_SELF,
            PermissionGroup::CLIENTS_AND_PROJECTS_ALL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function levelsForGroup(PermissionGroup $group): array
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
    public static function readPermissionNames(): array
    {
        $names = [];

        foreach (self::accessGroups() as $group) {
            array_push($names, ...self::levelsForGroup($group));
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function editPermissionNames(): array
    {
        return [
            'edit clients and projects self',
            'full clients and projects self',
            'edit clients and projects all',
            'full clients and projects all',
        ];
    }

    public static function middleware(): string
    {
        return 'permission:'.implode('|', self::readPermissionNames());
    }

    public static function userCanRead(?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyPermission(self::readPermissionNames());
    }

    public static function userCanEdit(?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyPermission(self::editPermissionNames());
    }

    public static function ensureUserCanEdit(?User $user = null): void
    {
        if (! self::userCanEdit($user)) {
            throw UnauthorizedException::forPermissions(self::editPermissionNames());
        }
    }

    public static function userCanSeeAll(?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyPermission(
            self::levelsForGroup(PermissionGroup::CLIENTS_AND_PROJECTS_ALL)
        );
    }

    public static function userCanSeeSelf(?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyPermission(
            self::levelsForGroup(PermissionGroup::CLIENTS_AND_PROJECTS_SELF)
        );
    }
}
