<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            throw ValidationException::withMessages([
                'userLogin' => __('auth.inactive'),
            ]);
        }

        Auth::login($user);

        return $user;
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
}
