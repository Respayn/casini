<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\SerpKeyword;
use App\Models\SerpTask;
use App\Models\SearchEngine;
use App\Models\SerpPosition;
use Src\Domain\Serp\SerpPositionRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;
use Illuminate\Database\Eloquent\Builder;

class EloquentSerpPositionRepository implements SerpPositionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTopPercentages(
        int $projectId,
        DateTimeRange $period,
        string $searchEngineCode = 'yandex'
    ): array {
        // 1. Все ключевые фразы проекта
        $keywords = SerpKeyword::where('project_id', $projectId)->pluck('id');

        if ($keywords->isEmpty()) {
            return [
                'top_3' => 0.0,
                'top_5' => 0.0,
                'top_10' => 0.0,
            ];
        }

        // 2. Подзапрос для получения последней позиции каждой фразы в периоде
        $latestPositionsSubquery = SerpPosition::query()
            ->selectRaw('serp_task_id, MAX(check_date) as latest_date')
            ->whereBetween('check_date', [
                $period->getStart()?->format('Y-m-d'),
                $period->getEnd()?->format('Y-m-d'),
            ])
            ->groupBy('serp_task_id');

        // 3. Собираем основной запрос
        $query = SerpTask::query()
            ->join('search_engines', 'serp_tasks.search_engine_id', '=', 'search_engines.id')
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
            ->whereIn('serp_tasks.serp_keyword_id', $keywords);

        // 4. Получаем все строки с позициями (position может быть NULL)
        $positions = $query->get(['serp_positions.position']);

        $total = $positions->count();
        if ($total === 0) {
            return [
                'top_3' => 0.0,
                'top_5' => 0.0,
                'top_10' => 0.0,
            ];
        }

        $top3 = $positions->where('position', '<=', 3)->whereNotNull('position')->count();
        $top5 = $positions->where('position', '<=', 5)->whereNotNull('position')->count();
        $top10 = $positions->where('position', '<=', 10)->whereNotNull('position')->count();

        return [
            'top_3' => round($top3 / $total * 100, 1),
            'top_5' => round($top5 / $total * 100, 1),
            'top_10' => round($top10 / $total * 100, 1),
        ];
    }
}