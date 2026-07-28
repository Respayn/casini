<?php

use App\Enums\PermissionGroup;
use App\Http\Controllers\YandexDirectOAuthController;
use App\Http\Controllers\YandexMetrikaAuthController;
use App\Livewire\LandingPage;
use App\Livewire\PrivacyPage;
use App\Support\ClientsAndProjectsPermissions;
use App\Support\SystemSettingsSectionPermissions;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('system-settings')
        ->name('system-settings.')
        ->group(function () {
            Route::middleware([
                SystemSettingsSectionPermissions::middleware(PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES),
            ])->group(function () {
                Route::livewire('/dictionaries', 'pages::system-settings.dictionary-list')->name('dictionaries');
            });

            Route::middleware([
                ClientsAndProjectsPermissions::middleware(),
            ])->group(function () {
                Route::livewire('/clients-and-projects', 'pages::system-settings.clients-and-projects')->name('clients-and-projects');
                Route::livewire('/clients-and-projects/project/{projectId?}', 'pages::system-settings.client-project-form')->name('clients-and-projects.projects.manage');
            });

            Route::middleware([
                SystemSettingsSectionPermissions::middleware(PermissionGroup::SYSTEM_SETTINGS),
            ])->group(function () {
                Route::livewire('/agency/{agency?}', 'pages::system-settings.agency-settings')->name('agency');
                Route::livewire('/agency', 'pages::system-settings.agency-settings')->name('agency.default');
            });

            Route::middleware([
                SystemSettingsSectionPermissions::middleware(PermissionGroup::SYSTEM_SETTINGS_USERS),
            ])->group(function () {
                Route::livewire('/users', 'pages::system-settings.users-list')->name('users');
                Route::livewire('/users/create', 'pages::system-settings.users-create')->name('users.create');
            });

            Route::middleware(['can.access.user.edit'])->group(function () {
                Route::livewire('/users/{user}/edit', 'pages::system-settings.users-edit')->name('users.edit');
            });

            Route::middleware([
                SystemSettingsSectionPermissions::middleware(PermissionGroup::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS),
            ])->group(function () {
                Route::livewire('/roles-and-permissions', 'pages::system-settings.roles-and-permissions')->name('roles-and-permissions');
            });
        });

    Route::livewire('/notifications', 'pages::notifications-list')->name('notifications.index');

    Route::middleware(['permission:read channels|full channels'])->group(function () {
        Route::livewire('/channels', 'pages::channels')->name('channels');
    });

    Route::middleware(['permission:read statistics|full statistics'])->group(function () {
        Route::livewire('/statistics', 'pages::statistics')->name('statistics');
    });

    Route::middleware(['permission:read reports|full reports'])->group(function () {
        Route::livewire('/reports', 'pages::reports')->name('reports');
        Route::livewire('/reports/create', 'pages::reports-create')->name('reports.create');
    });

    Route::livewire('/templates', 'pages::templates')->name('templates');

    Route::middleware(['permission:read planning|full planning'])->group(function () {
        Route::livewire('/planning', 'pages::planning')->name('planning');
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
});

Route::livewire('/', LandingPage::class)->name('landing');
Route::livewire('/privacy', PrivacyPage::class)->name('privacy');

require __DIR__.'/auth.php';
