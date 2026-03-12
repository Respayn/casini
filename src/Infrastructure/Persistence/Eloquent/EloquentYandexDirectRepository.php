<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\YandexDirectCampaignStats as EloquentYandexDirectCampaignStats;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;

class EloquentYandexDirectRepository implements YandexDirectRepositoryInterface
{
    /**
     * @param int $projectId
     * @param DateTimeRange|null $period
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
            ->map(fn(EloquentYandexDirectCampaignStats $stats) => $this->mapToEntity($stats))
            ->toArray();
    }

    private function mapToEntity(EloquentYandexDirectCampaignStats $stats): YandexDirectCampaignStats
    {
        return YandexDirectCampaignStats::restore($stats->toArray());
    }
}
