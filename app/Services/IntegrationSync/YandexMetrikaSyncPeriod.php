<?php

namespace App\Services\IntegrationSync;

use Illuminate\Support\Carbon;

/**
 * Период запроса к API Метрики. В БД Метрики лежат суммы за месяц,
 * поэтому запрос всегда начинается с 1-го числа месяца.
 */
class YandexMetrikaSyncPeriod
{
    /**
     * Ночной съём: месяц вчерашней даты, с 1-го числа по вчера.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function nightly(Carbon $targetDate, string $syncEnabledAt): ?array
    {
        return self::range($targetDate->copy()->startOfMonth(), $targetDate, $syncEnabledAt);
    }

    /**
     * Ручное обновление и backfill: все месяцы периода, но не раньше месяца включения съёма.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function range(Carbon $from, Carbon $to, string $syncEnabledAt): ?array
    {
        $enabledAt = self::parseDate($syncEnabledAt);

        if ($enabledAt === null) {
            return null;
        }

        $to = $to->copy()->startOfDay();

        if ($enabledAt->greaterThan($to)) {
            return null;
        }

        $from = $from->copy()->startOfMonth()->startOfDay();
        $enabledMonth = $enabledAt->copy()->startOfMonth();

        if ($from->lessThan($enabledMonth)) {
            $from = $enabledMonth;
        }

        return [$from, $to];
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
