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

    /**
     * Окно для факта: срез, который ещё не начался, пустой; конец не позже сегодня.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function resolveForFact(
        Carbon $bucketFrom,
        Carbon $bucketTo,
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        bool $accumulate,
        Carbon $today,
    ): ?array {
        $today = $today->copy()->startOfDay();

        if ($bucketFrom->copy()->startOfDay()->greaterThan($today)) {
            return null;
        }

        [$from, $to] = self::resolve(
            $bucketFrom,
            $bucketTo,
            $detailLevel,
            $gridMonth,
            $periodFrom,
            $accumulate,
        );

        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            return null;
        }

        return [$from, $to];
    }
}
