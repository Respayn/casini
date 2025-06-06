<?php

namespace App\Providers;

use App\Repositories\AgencySettingsRepository;
use App\Repositories\CallibriLeadRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\Interfaces\AgencySettingsRepositoryInterface;
use App\Repositories\Interfaces\CallibriLeadRepositoryInterface;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use App\Repositories\Interfaces\ProjectUtmMappingRepositoryInterface;
use App\Repositories\ProjectUtmMappingRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AgencySettingsRepositoryInterface::class, AgencySettingsRepository::class);
        $this->app->bind(ProjectUtmMappingRepositoryInterface::class, ProjectUtmMappingRepository::class);
        $this->app->bind(IntegrationRepositoryInterface::class, IntegrationRepository::class);
        $this->app->bind(CallibriLeadRepositoryInterface::class, CallibriLeadRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Arr::macro('keyByRecursive', function (array $array, callable $callable): array {
            return collect($array)->keyByRecursive($callable)->toArray();
        });

        Collection::macro('keyByRecursive', function (callable $callable) {
            return $this->mapWithKeys(function ($value, $key) use ($callable) {
                return [$callable($key) => is_array($value) ? Arr::keyByRecursive($value, $callable) : $value];
            });
        });

        foreach (['camel', 'kebab', 'lower', 'snake', 'studly', 'upper'] as $method) {
            Arr::macro($method, function (array $array) use ($method): array {
                return Arr::keyByRecursive($array, "Str::{$method}");
            });

            Collection::macro($method, function () use ($method): Collection {
                return new static(Arr::{$method}($this->items));
            });
        }
    }
}
