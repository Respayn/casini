<?php

namespace App\Providers;

use App\Clients\YandexDirect\YandexDirectClient;
use Illuminate\Support\ServiceProvider;

class YandexDirectServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(YandexDirectClient::class, function ($app) {
            return new YandexDirectClient(
                config('services.yandex_direct.token'),
                config('services.yandex_direct.client_login'),
                config('services.yandex_direct.use_sandbox') ?? false
            );
        });
    }

    public function boot()
    {
        //
    }
}
