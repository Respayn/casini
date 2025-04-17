<?php

use App\Http\Controllers\YandexDirectOAuthController;
use App\Livewire\Demo;
use App\Livewire\SystemSettings\ClientsAndProjects;
use App\Livewire\SystemSettings\ClientAndProjects\CreateClient;
use App\Livewire\SystemSettings\ClientAndProjects\ClientProjectFormModel;
use App\Livewire\SystemSettings\DictionaryList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('system-settings')->name('system-settings.')->group(function () {
        Route::get('/dictionaries', DictionaryList::class)->name('dictionaries');
        Route::get('/clients-and-projects', ClientsAndProjects::class)->name('clients-and-projects');
        Route::get('/clients-and-projects/project/{projectId?}', ClientProjectFormModel::class)->name('clients-and-projects.projects.manage');
        Route::get('/clients-and-projects/client/create', CreateClient::class)->name('clients-and-projects.clients.create');
    });

    Route::get('/yandex-direct/connect', [YandexDirectOAuthController::class, 'redirect'])
        ->name('yandex_direct.oauth.redirect');

    Route::get('/yandex-direct/callback', [YandexDirectOAuthController::class, 'callback'])
        ->name('yandex_direct.oauth.callback');

    Route::get('/', Demo::class)->name('demo');
});

require __DIR__ . '/auth.php';
