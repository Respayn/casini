<?php

namespace App\Providers;

use App\Repositories\Interfaces\RateRepositoryInterface;
use App\Repositories\RateRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(RateRepositoryInterface::class, RateRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
