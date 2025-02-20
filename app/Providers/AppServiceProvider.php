<?php

namespace App\Providers;

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
        //
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
