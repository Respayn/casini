<?php

namespace App\Services\Auth;

use App\Mail\UserInvitationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class UserInvitationService
{
    public function send(User $user): void
    {
        Mail::to($user->email)->send(new UserInvitationMail(
            email: $user->email,
            login: $user->login,
            inviteUrl: $this->inviteUrl($user),
        ));
    }

    public function inviteUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'invitation.accept',
            now()->addDays(7),
            ['user' => $user->id],
        );
    }

    public function hasValidSignature(User $user, string $expires, string $signature): bool
    {
        if ($expires === '' || $signature === '') {
            return false;
        }

        $request = Request::create(
            route('invitation.accept', ['user' => $user->id]).'?'.http_build_query([
                'expires' => $expires,
                'signature' => $signature,
            ])
        );

        return URL::hasValidSignature($request);
    }

    public function canAccept(User $user): bool
    {
        return ! $user->is_active && $user->email_verified_at === null;
    }

    public function accept(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();
    }
}
