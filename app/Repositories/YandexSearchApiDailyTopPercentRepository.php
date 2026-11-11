<?php

namespace App\Repositories;

use App\Models\YandexSearchApiDailyTopPercent;

class YandexSearchApiDailyTopPercentRepository
{
    /**
     * Средний % в топ-10 за период. Нет строк → null.
     */
    public function averageTop10Percent(int $projectId, string $dateFrom, string $dateTo): ?float
    {
        $rows = YandexSearchApiDailyTopPercent::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get(['top_10_percent']);

        if ($rows->isEmpty()) {
            return null;
        }

        return round((float) $rows->avg('top_10_percent'), 2);
    }
}
