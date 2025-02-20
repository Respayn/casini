<?php

use App\Livewire\Demo;
use App\Livewire\SystemSettings\Dictionaries;
use Illuminate\Support\Facades\Route;

Route::get('/', Demo::class)->name('demo');

Route::prefix('system-settings')->name('system-settings.')->group(function() {
    Route::get('/dictionaries', Dictionaries::class)->name('dictionaries');
});