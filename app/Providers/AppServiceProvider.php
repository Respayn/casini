<?php

namespace App\Providers;

use App\Contracts\ChannelReportServiceInterface;
use App\Repositories\AgencySettingsRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\Interfaces\AgencySettingsRepositoryInterface;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use App\Repositories\Interfaces\ProjectUtmMappingRepositoryInterface;
use App\Repositories\ProjectUtmMappingRepository;
use App\Services\Channels\ChannelReportService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Src\Application\Clients\ClientReadRepositoryInterface;
use Src\Application\Reports\Generate\ReportDataProviderInterface;
use Src\Application\Reports\Generate\ReportGeneratorInterface;
use Src\Application\Reports\GetList\ReportsListDataProviderInterface;
use Src\Domain\Clients\ClientRepositoryInterface;
use Src\Application\ColumnSettings\ColumnSettingsRepositoryInterface;
use Src\Domain\Agencies\AgencyRepositoryInterface;
use Src\Domain\CompletedWorks\CompletedWorkRepositoryInterface;
use Src\Domain\Leads\CallibriLeadRepositoryInterface;
use Src\Domain\Projects\ProjectPlanValueRepositoryInterface;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Reports\ReportRepositoryInterface;
use Src\Domain\Templates\TemplateRepositoryInterface;
use Src\Domain\Serp\SerpPositionRepositoryInterface;
use Src\Domain\Users\UserRepositoryInterface;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Src\Infrastructure\Persistence\ClientReadRepository;
use Src\Infrastructure\Persistence\ClientRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentAgencyRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentCallibriLeadRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentCompletedWorkRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentProjectPlanValueRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentSerpPositionRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentYandexDirectRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentYandexMetrikaRepository;
use Src\Infrastructure\Persistence\EloquentColumnSettingsRepository;
use Src\Infrastructure\Persistence\ProjectRepository;
use Src\Infrastructure\Persistence\ReportRepository;
use Src\Infrastructure\Persistence\TemplateRepository;
use Src\Infrastructure\Persistence\UserRepository;
use Src\Infrastructure\Queries\ReportsListDataProvider;
use Src\Infrastructure\Reports\ReportDataProvider;
use Src\Infrastructure\Reports\ReportGenerator;

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
        $this->app->bind(ChannelReportServiceInterface::class, ChannelReportService::class);

        // Привязка по Clean Architecture, отрефакторить остальное на неё
        $this->app->bind(AgencyRepositoryInterface::class, EloquentAgencyRepository::class);
        $this->app->bind(CallibriLeadRepositoryInterface::class, EloquentCallibriLeadRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(ClientReadRepositoryInterface::class, ClientReadRepository::class);
        $this->app->bind(ColumnSettingsRepositoryInterface::class, EloquentColumnSettingsRepository::class);
        $this->app->bind(CompletedWorkRepositoryInterface::class, EloquentCompletedWorkRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(ProjectPlanValueRepositoryInterface::class, EloquentProjectPlanValueRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(SerpPositionRepositoryInterface::class, EloquentSerpPositionRepository::class);
        $this->app->bind(TemplateRepositoryInterface::class, TemplateRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(YandexDirectRepositoryInterface::class, EloquentYandexDirectRepository::class);
        $this->app->bind(YandexMetrikaRepositoryInterface::class, EloquentYandexMetrikaRepository::class);

        $this->app->bind(ReportGeneratorInterface::class, ReportGenerator::class);
        $this->app->bind(ReportsListDataProviderInterface::class, ReportsListDataProvider::class);
        $this->app->bind(ReportDataProviderInterface::class, ReportDataProvider::class);
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
