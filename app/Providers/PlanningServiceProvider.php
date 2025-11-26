<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Planning\Application\Factories\ContextAdKpiParametersSchemaFactory;
use Src\Planning\Application\Factories\SeoPromotionKpiParametersSchemaFactory;
use Src\Planning\Application\Repositories\ProjectPlanRepositoryInterface;
use Src\Planning\Application\Repositories\ProjectRepositoryInterface;
use Src\Planning\Application\Services\KpiParametersSchemaService;
use Src\Planning\Infrastructure\EloquentProjectPlanRepository;
use Src\Planning\Infrastructure\EloquentProjectRepository;

class PlanningServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ContextAdKpiParametersSchemaFactory::class);
        $this->app->singleton(SeoPromotionKpiParametersSchemaFactory::class);
        $this->app->singleton(KpiParametersSchemaService::class);

        $this->app->bind(ProjectPlanRepositoryInterface::class, EloquentProjectPlanRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, EloquentProjectRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
