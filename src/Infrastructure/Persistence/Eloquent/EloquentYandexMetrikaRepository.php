<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\YandexMetrikaGoalConversion as EloquentYandexMetrikaGoalConversion;
use App\Models\YandexMetrikaGoalDirectSummary as EloquentYandexMetrikaGoalDirectSummary;
use App\Models\YandexMetrikaGoalUtm as EloquentYandexMetrikaGoalUtm;
use App\Models\YandexMetrikaSearchEnginesStats as EloquentYandexMetrikaSearchEnginesStats;
use App\Models\YandexMetrikaVisitsGeo as EloquentYandexMetrikaVisitsGeo;
use App\Models\YandexMetrikaVisitsSearchQueries as EloquentYandexMetrikaVisitsSearchQueries;
use Carbon\Carbon;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexMetrika\YandexMetrikaGoalConversion;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectSummary;
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

    /**
     * {@inheritdoc}
     */
    public function upsertSearchEnginesConversions(int $projectId, string $searchEngine, string $month, int $conversions): void
    {
        $monthKey = Carbon::parse($month)->startOfMonth()->format('Y-m-d');

        $existing = EloquentYandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $projectId)
            ->where('search_engine', $searchEngine)
            ->where('month', $monthKey)
            ->first();

        if ($existing !== null) {
            $existing->conversions = $conversions;
            $existing->save();

            return;
        }

        EloquentYandexMetrikaSearchEnginesStats::query()->create([
            'project_id' => $projectId,
            'search_engine' => $searchEngine,
            'month' => $monthKey,
            'visits' => 0,
            'conversions' => $conversions,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceGoalUtmRows(int $projectId, string $dateFrom, string $dateTo, array $rows): void
    {
        EloquentYandexMetrikaGoalUtm::query()
            ->where('project_id', $projectId)
            ->where('achieved_date', '>=', $dateFrom)
            ->where('achieved_date', '<=', $dateTo)
            ->delete();

        if ($rows === []) {
            return;
        }

        $now = now();
        $inserts = array_map(fn (array $row) => [
            'project_id' => $projectId,
            'goal_name' => $row['goal_name'],
            'achieved_date' => $row['achieved_date'],
            'utm_source' => $row['utm_source'] ?? null,
            'utm_medium' => $row['utm_medium'] ?? null,
            'utm_campaign' => $row['utm_campaign'] ?? null,
            'utm_content' => $row['utm_content'] ?? null,
            'utm_term' => $row['utm_term'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        foreach (array_chunk($inserts, 500) as $chunk) {
            EloquentYandexMetrikaGoalUtm::query()->insert($chunk);
        }
    }

    public function upsertGoalConversions(int $projectId, array $rows): void
    {
        foreach ($rows as $row) {
            EloquentYandexMetrikaGoalConversion::query()->updateOrCreate(
                [
                    'project_id' => $projectId,
                    'goal_name' => $row['goal_name'],
                    'month' => $row['month'],
                ],
                [
                    'conversions' => $row['conversions'],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getGoalDirectSummaryStats(int $projectId, DateTimeRange $period): array
    {
        $query = EloquentYandexMetrikaGoalDirectSummary::query()
            ->where('project_id', '=', $projectId);

        if ($period->start !== null) {
            $query->where('month', '>=', $period->start->format('Y-m-01'));
        }

        if ($period->end !== null) {
            $query->where('month', '<=', $period->end->format('Y-m-01'));
        }

        return $query->get()
            ->map(fn(EloquentYandexMetrikaGoalDirectSummary $model) => $this->mapGoalDirectSummaryToEntity($model))
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function upsertGoalDirectSummary(int $projectId, array $rows): void
    {
        foreach ($rows as $row) {
            EloquentYandexMetrikaGoalDirectSummary::query()->updateOrCreate(
                [
                    'project_id' => $projectId,
                    'goal_name' => $row['goal_name'],
                    'month' => $row['month'],
                ],
                [
                    'conversions' => $row['conversions'],
                ]
            );
        }
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

    private function mapGoalDirectSummaryToEntity(EloquentYandexMetrikaGoalDirectSummary $model): YandexMetrikaGoalDirectSummary
    {
        return YandexMetrikaGoalDirectSummary::restore($model->toArray());
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
