<?php

namespace App\Providers;

use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\RateRepositoryInterface;
use App\Repositories\Interfaces\WorkActRepositoryInterface;
use App\Repositories\PaymentRepository;
use App\Repositories\RateRepository;
use App\Repositories\WorkActRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(RateRepositoryInterface::class, RateRepository::class);
        $this->app->bind(WorkActRepositoryInterface::class, WorkActRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
