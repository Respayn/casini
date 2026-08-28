<?php

namespace App\Services\Auth;

use App\Enums\UserAccountStatus;
use App\Mail\VerifyRegistrationMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class LoginService
{
    /**
     * @throws ValidationException
     */
    public function attempt(string $loginOrEmail, string $password): User
    {
        $user = $this->findByLoginOrEmail($loginOrEmail);

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'userLogin' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            $status = $user->accountStatus();

            throw ValidationException::withMessages([
                $status->value => $this->inactiveMessage($user),
            ]);
        }

        Auth::login($user);

        return $user;
    }

    public function inactiveMessage(User $user): string
    {
        return $user->accountStatus() === UserAccountStatus::PendingEmail
            ? __('auth.pending_email')
            : __('auth.inactive');
    }

    public function findByLoginOrEmail(string $loginOrEmail): ?User
    {
        $identifier = mb_strtolower(trim($loginOrEmail));

        if ($identifier === '') {
            return null;
        }

        return User::query()
            ->where(function ($query) use ($identifier): void {
                $query->whereRaw('LOWER(login) = ?', [$identifier])
                    ->orWhereRaw('LOWER(email) = ?', [$identifier]);
            })
            ->first();
    }

    /**
     * Повторная отправка письма подтверждения.
     * Требует верный пароль; не раскрывает, существует ли другой аккаунт.
     *
     * @return bool true, если письмо реально отправлено
     */
    public function resendVerificationEmail(string $loginOrEmail, string $password): bool
    {
        $user = $this->findByLoginOrEmail($loginOrEmail);

        if ($user === null || ! Hash::check($password, $user->password)) {
            return false;
        }

        if ($user->accountStatus() !== UserAccountStatus::PendingEmail) {
            return false;
        }

        $this->sendVerificationEmail($user);

        return true;
    }

    public function sendVerificationEmail(User $user): void
    {
        $verifyUrl = URL::temporarySignedRoute(
            'register.verify',
            now()->addDay(),
            ['user' => $user->id],
        );

        Mail::to($user->email)->send(new VerifyRegistrationMail(
            email: $user->email,
            verifyUrl: $verifyUrl,
        ));
    }
}
