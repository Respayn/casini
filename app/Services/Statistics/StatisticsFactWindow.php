<?php

namespace App\Services\Statistics;

use App\Domain\Statistics\Enums\StatisticsReportDetailLevel;
use Illuminate\Support\Carbon;

/**
 * Окно плана/факта для среза Статистики с учётом настройки «накапливаются».
 */
final class StatisticsFactWindow
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolve(
        Carbon $bucketFrom,
        Carbon $bucketTo,
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        bool $accumulate,
    ): array {
        $to = $bucketTo->copy()->startOfDay();

        if (! $accumulate) {
            return [$bucketFrom->copy()->startOfDay(), $to];
        }

        $start = $detailLevel === StatisticsReportDetailLevel::BY_MONTH
            ? $periodFrom->copy()->startOfMonth()->startOfDay()
            : $gridMonth->copy()->startOfMonth()->startOfDay();

        if ($start->greaterThan($bucketFrom)) {
            $start = $bucketFrom->copy()->startOfDay();
        }

        return [$start, $to];
    }
}
