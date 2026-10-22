<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\SerpPosition;
use App\Models\SerpTask;
use Src\Domain\Serp\SerpPositionRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class EloquentSerpPositionRepository implements SerpPositionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getKeywordPositions(
        int $projectId,
        DateTimeRange $period,
        string $searchEngineCode,
        int $regionGeoId,
    ): array {
        $latestPositionsSubquery = SerpPosition::query()
            ->selectRaw('serp_task_id, MAX(check_date) as latest_date')
            ->whereBetween('check_date', [
                $period->getStart()?->format('Y-m-d'),
                $period->getEnd()?->format('Y-m-d'),
            ])
            ->groupBy('serp_task_id');

        $results = SerpTask::query()
            ->select(['serp_keywords.phrase', 'serp_positions.position'])
            ->join('search_engines', 'serp_tasks.search_engine_id', '=', 'search_engines.id')
            ->join('serp_regions', 'serp_tasks.serp_region_id', '=', 'serp_regions.id')
            ->join('serp_keywords', 'serp_tasks.serp_keyword_id', '=', 'serp_keywords.id')
            ->leftJoinSub(
                $latestPositionsSubquery,
                'latest_positions',
                fn ($join) => $join->on('serp_tasks.id', '=', 'latest_positions.serp_task_id')
            )
            ->leftJoin(
                'serp_positions',
                fn ($join) => $join
                    ->on('serp_positions.serp_task_id', '=', 'serp_tasks.id')
                    ->on('serp_positions.check_date', '=', 'latest_positions.latest_date')
            )
            ->where('serp_tasks.project_id', $projectId)
            ->where('search_engines.code', $searchEngineCode)
            ->where('serp_regions.geo_id', (string) $regionGeoId)
            ->where('serp_tasks.is_active', true)
            ->orderBy('serp_keywords.phrase')
            ->get();

        return $results->map(fn ($row) => [
            'phrase' => $row->phrase,
            'position' => $row->position,
        ])->toArray();
    }
}
