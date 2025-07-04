<?php

use App\Http\Controllers\YandexDirectOAuthController;
use App\Http\Controllers\YandexMetrikaAuthController;
use App\Livewire\Demo;
use App\Livewire\SystemSettings\Agency\AgencySettingsComponent;
use App\Livewire\SystemSettings\ClientsAndProjects;
use App\Livewire\SystemSettings\ClientAndProjects\CreateClient;
use App\Livewire\SystemSettings\ClientAndProjects\ClientProjectFormModel;
use App\Livewire\SystemSettings\CreateAgencyComponent;
use App\Livewire\SystemSettings\DictionaryList;
use App\Livewire\Users\UsersList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('system-settings')->name('system-settings.')->group(function () {
        Route::get('/dictionaries', DictionaryList::class)->name('dictionaries');
        Route::get('/clients-and-projects', ClientsAndProjects::class)->name('clients-and-projects');
        Route::get('/clients-and-projects/project/{projectId?}', ClientProjectFormModel::class)->name('clients-and-projects.projects.manage');
        Route::get('/clients-and-projects/client/create', CreateClient::class)->name('clients-and-projects.clients.create');

        Route::get('/agency/{agency?}', AgencySettingsComponent::class)->name('agency');
        Route::get('/agency', AgencySettingsComponent::class)->name('agency.default');
        Route::get('/agency/create', CreateAgencyComponent::class)->name('agency.create');

        Route::get('/users', UsersList::class)->name('system-settings.users');
    });


    Route::prefix('yandex-direct')->group(function () {
        Route::get('/connect', [YandexDirectOAuthController::class, 'redirect'])
            ->name('yandex_direct.oauth.redirect');
        Route::get('/callback', [YandexDirectOAuthController::class, 'callback'])
            ->name('yandex_direct.oauth.callback');
    });

    Route::prefix('yandex-metrika')->group(function () {
        Route::get('/connect', [YandexMetrikaAuthController::class, 'redirect'])
            ->name('yandex-metrika.auth');

        Route::get('/callback', [YandexMetrikaAuthController::class, 'callback'])
            ->name('yandex-metrika.callback');
    });

    Route::get('/', Demo::class)->name('demo');
});

require __DIR__ . '/auth.php';
