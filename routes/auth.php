<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');
    Route::livewire('register', 'pages::auth.register')->name('register');
    Route::livewire('forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('reset-password', 'auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});
