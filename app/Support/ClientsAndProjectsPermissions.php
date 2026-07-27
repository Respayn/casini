<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class ClientsAndProjectsPermissions
{
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
}
