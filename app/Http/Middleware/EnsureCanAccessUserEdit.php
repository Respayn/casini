<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SystemSettingsSectionPermissions;
use App\Support\UserProfileAccess;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessUserEdit
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $target */
        $target = $request->route('user');

        if (! $target instanceof User) {
            throw UnauthorizedException::forPermissions(
                SystemSettingsSectionPermissions::readPermissionNames(
                    SystemSettingsSectionPermissions::users()
                )
            );
        }

        if (! UserProfileAccess::canViewUser($target)) {
            throw UnauthorizedException::forPermissions(
                SystemSettingsSectionPermissions::readPermissionNames(
                    SystemSettingsSectionPermissions::users()
                )
            );
        }

        return $next($request);
    }
}
