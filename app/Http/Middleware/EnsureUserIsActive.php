<?php

namespace App\Http\Middleware;

use App\Enums\UserAccountStatus;
use App\Services\Auth\LoginService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(
        private readonly LoginService $loginService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        $status = UserAccountStatus::fromUser($user);
        $flashKey = $status === UserAccountStatus::PendingEmail
            ? 'pending_email'
            : 'inactive';
        $message = $this->loginService->inactiveMessage($user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with($flashKey, $message);
    }
}
