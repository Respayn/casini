<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\YandexMetrikaGoalConversion as EloquentYandexMetrikaGoalConversion;
use App\Models\YandexMetrikaGoalUtm as EloquentYandexMetrikaGoalUtm;
use App\Models\YandexMetrikaSearchEnginesStats as EloquentYandexMetrikaSearchEnginesStats;
use App\Models\YandexMetrikaVisitsGeo as EloquentYandexMetrikaVisitsGeo;
use App\Models\YandexMetrikaVisitsSearchQueries as EloquentYandexMetrikaVisitsSearchQueries;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexMetrika\YandexMetrikaGoalConversion;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats;
use Src\Domain\YandexMetrika\YandexMetrikaVisitsGeo;
use Src\Domain\YandexMetrika\YandexMetrikaVisitsSearchQueries;

class EloquentYandexMetrikaRepository implements YandexMetrikaRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSearchEnginesStats(int $projectId, DateTimeRange $period): array
    {
        $query = EloquentYandexMetrikaSearchEnginesStats::query()
            ->where('project_id', '=', $projectId);

        if ($period->start !== null) {
            $query->where('month', '>=', $period->start->format('Y-m-01'));
        }

        if ($period->end !== null) {
            $query->where('month', '<=', $period->end->format('Y-m-01'));
        }

        return $query->get()
            ->map(fn(EloquentYandexMetrikaSearchEnginesStats $stats) => $this->mapSearchEnginesStatsToEntity($stats))
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getGoalUtmStats(int $projectId, DateTimeRange $period): array
    {
        $query = EloquentYandexMetrikaGoalUtm::query()
            ->where('project_id', '=', $projectId);

        if ($period->start !== null) {
            $query->where('achieved_date', '>=', $period->start->format('Y-m-d'));
        }

        if ($period->end !== null) {
            $query->where('achieved_date', '<=', $period->end->format('Y-m-d'));
        }

        return $query->get()
            ->map(fn(EloquentYandexMetrikaGoalUtm $model) => $this->mapGoalUtmToEntity($model))
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getGoalConversionsStats(int $projectId, DateTimeRange $period): array
    {
        $query = EloquentYandexMetrikaGoalConversion::query()
            ->where('project_id', '=', $projectId);

        if ($period->start !== null) {
            $query->where('month', '>=', $period->start->format('Y-m-01'));
        }

        if ($period->end !== null) {
            $query->where('month', '<=', $period->end->format('Y-m-01'));
        }

        return $query->get()
            ->map(fn(EloquentYandexMetrikaGoalConversion $model) => $this->mapGoalConversionToEntity($model))
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getVisitsGeoStats(int $projectId, DateTimeRange $period): array
    {
        $query = EloquentYandexMetrikaVisitsGeo::query()
            ->where('project_id', '=', $projectId);

        if ($period->start !== null) {
            $query->where('month', '>=', $period->start->format('Y-m-01'));
        }

        if ($period->end !== null) {
            $query->where('month', '<=', $period->end->format('Y-m-01'));
        }

        return $query->get()
            ->map(fn(EloquentYandexMetrikaVisitsGeo $model) => $this->mapVisitsGeoToEntity($model))
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getVisitsSearchQueriesStats(int $projectId, DateTimeRange $period): array
    {
        $query = EloquentYandexMetrikaVisitsSearchQueries::query()
            ->where('project_id', '=', $projectId);

        if ($period->start !== null) {
            $query->where('month', '>=', $period->start->format('Y-m-01'));
        }

        if ($period->end !== null) {
            $query->where('month', '<=', $period->end->format('Y-m-01'));
        }

        return $query->get()
            ->map(fn(EloquentYandexMetrikaVisitsSearchQueries $model) => $this->mapVisitsSearchQueriesToEntity($model))
            ->toArray();
    }

    private function mapSearchEnginesStatsToEntity(EloquentYandexMetrikaSearchEnginesStats $stats): YandexMetrikaSearchEnginesStats
    {
        return YandexMetrikaSearchEnginesStats::restore($stats->toArray());
    }

    private function mapGoalUtmToEntity(EloquentYandexMetrikaGoalUtm $model): YandexMetrikaGoalUtm
    {
        return YandexMetrikaGoalUtm::restore($model->toArray());
    }

    private function mapGoalConversionToEntity(EloquentYandexMetrikaGoalConversion $model): YandexMetrikaGoalConversion
    {
        return YandexMetrikaGoalConversion::restore($model->toArray());
    }

    private function mapVisitsGeoToEntity(EloquentYandexMetrikaVisitsGeo $model): YandexMetrikaVisitsGeo
    {
        return YandexMetrikaVisitsGeo::restore($model->toArray());
    }

    private function mapVisitsSearchQueriesToEntity(EloquentYandexMetrikaVisitsSearchQueries $model): YandexMetrikaVisitsSearchQueries
    {
        return YandexMetrikaVisitsSearchQueries::restore($model->toArray());
    }
}
