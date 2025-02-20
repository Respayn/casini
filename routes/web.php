<?php

use App\Livewire\Demo;
use App\Livewire\SystemSettings\DictionaryList;
use Illuminate\Support\Facades\Route;

Route::get('/', Demo::class)->name('demo');

Route::prefix('system-settings')->name('system-settings.')->group(function() {
    Route::get('/dictionaries', DictionaryList::class)->name('dictionaries');
});