<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\YandexDirectCampaignStats as EloquentYandexDirectCampaignStats;
use App\Models\YandexDirectDailySpending;
use Illuminate\Support\Carbon;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;

class EloquentYandexDirectRepository implements YandexDirectRepositoryInterface
{
    /**
     * @return YandexDirectCampaignStats[]
     */
    public function findByProjectId(int $projectId, ?DateTimeRange $period = null): array
    {
        $query = EloquentYandexDirectCampaignStats::query()
            ->where('project_id', '=', $projectId);

        if ($period !== null) {
            if ($period->start !== null) {
                $query->where('date', '>=', $period->start);
            }

            if ($period->end !== null) {
                $query->where('date', '<=', $period->end);
            }
        }

        return $query->get()
            ->map(fn (EloquentYandexDirectCampaignStats $stats) => $this->mapToEntity($stats))
            ->toArray();
    }

    public function sumDailySpendings(int $projectId, string $dateFrom, string $dateTo, bool $includeVat): ?float
    {
        $column = $includeVat ? 'cost_with_vat' : 'cost_without_vat';
        $rows = YandexDirectDailySpending::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get(['date', $column]);

        if ($rows->isEmpty()) {
            return null;
        }

        return round((float) $rows->sum($column), 2);
    }

    public function clicksByDay(int $projectId, string $dateFrom, string $dateTo): array
    {
        $rows = EloquentYandexDirectCampaignStats::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get(['date', 'clicks']);

        $map = [];
        foreach ($rows as $row) {
            $day = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : Carbon::parse((string) $row->date)->toDateString();
            $map[$day] = ($map[$day] ?? 0) + (int) $row->clicks;
        }

        ksort($map);

        return $map;
    }

    public function replaceCampaignStatsForPeriod(int $projectId, string $dateFrom, string $dateTo, array $rows): void
    {
        EloquentYandexDirectCampaignStats::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->delete();

        foreach (array_chunk($rows, 500) as $chunk) {
            EloquentYandexDirectCampaignStats::query()->insert($chunk);
        }
    }

    private function mapToEntity(EloquentYandexDirectCampaignStats $stats): YandexDirectCampaignStats
    {
        return YandexDirectCampaignStats::restore($stats->toArray());
    }
}
