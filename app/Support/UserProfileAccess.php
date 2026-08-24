<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserProfileAccess
{
    public static function isOwnProfile(User $target, ?User $actor = null): bool
    {
        $actor ??= Auth::user();

        if ($actor === null) {
            return false;
        }

        return (int) $actor->id === (int) $target->id;
    }

    public static function canViewUser(User $target, ?User $actor = null): bool
    {
        $actor ??= Auth::user();

        if ($actor === null) {
            return false;
        }

        if (self::isOwnProfile($target, $actor)) {
            return true;
        }

        return SystemSettingsSectionPermissions::userCanRead(
            SystemSettingsSectionPermissions::users(),
            $actor
        );
    }

    public static function canSaveUser(User $target, ?User $actor = null): bool
    {
        $actor ??= Auth::user();

        if ($actor === null) {
            return false;
        }

        if (self::isOwnProfile($target, $actor)) {
            return true;
        }

        return self::canEditAdminFields($actor);
    }

    public static function canEditAdminFields(?User $actor = null): bool
    {
        return SystemSettingsSectionPermissions::userCanEdit(
            SystemSettingsSectionPermissions::users(),
            $actor
        );
    }

    /**
     * @return list<string>
     */
    public static function selfProfileFieldKeys(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone',
            'image_path',
            'enable_important_notifications',
            'enable_notifications',
            'password',
        ];
    }

    /**
     * @return list<string>
     */
    public static function adminFieldKeys(): array
    {
        return [
            'login',
            'is_active',
            'role_id',
            'rate_id',
            'megaplan_id',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeSavePayload(User $target, array $data, ?User $actor = null): array
    {
        $actor ??= Auth::user();

        if (self::canEditAdminFields($actor)) {
            return $data;
        }

        foreach (self::adminFieldKeys() as $key) {
            unset($data[$key]);
        }

        if (self::isOwnProfile($target, $actor)) {
            $allowed = array_flip(self::selfProfileFieldKeys());

            return array_intersect_key($data, $allowed);
        }

        return $data;
    }
}
