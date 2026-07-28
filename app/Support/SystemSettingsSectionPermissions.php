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

    /**
     * Порядок разделов для шестерёнки и «первого доступного» URL — как вкладки layout.
     *
     * @return list<array{check: callable(User): bool, route: string}>
     */
    public static function settingsEntryPoints(): array
    {
        return [
            [
                'check' => fn (User $user): bool => self::userCanRead(self::rolesAndPermissions(), $user),
                'route' => 'system-settings.roles-and-permissions',
            ],
            [
                'check' => fn (User $user): bool => self::userCanRead(self::users(), $user),
                'route' => 'system-settings.users',
            ],
            [
                'check' => fn (User $user): bool => ClientsAndProjectsPermissions::userCanRead($user),
                'route' => 'system-settings.clients-and-projects',
            ],
            [
                'check' => fn (User $user): bool => self::userCanRead(self::dictionaries(), $user),
                'route' => 'system-settings.dictionaries',
            ],
            [
                'check' => fn (User $user): bool => self::userCanRead(self::agency(), $user),
                'route' => 'system-settings.agency',
            ],
        ];
    }

    public static function userCanAccessAnySettingsSection(?User $user = null): bool
    {
        return self::firstAccessibleSettingsRouteName($user) !== null;
    }

    public static function firstAccessibleSettingsRouteName(?User $user = null): ?string
    {
        $user ??= Auth::user();

        if ($user === null) {
            return null;
        }

        foreach (self::settingsEntryPoints() as $entry) {
            if ($entry['check']($user)) {
                return $entry['route'];
            }
        }

        return null;
    }
}
