<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');

    if (config('app.registration_enabled')) {
        Route::livewire('register', 'pages::auth.register')->name('register');
    } else {
        Route::redirect('register', '/login')->name('register');
    }

    Route::livewire('forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});

Route::get('register/verify/{user}', function (User $user) {
    if (! $user->is_active) {
        $user->forceFill([
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();
    }

    return redirect()
        ->route('login')
        ->with('status', 'Email подтверждён. Теперь вы можете войти.');
})->middleware('signed')->name('register.verify');

Route::middleware('auth')->group(function () {
    Route::post('logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
