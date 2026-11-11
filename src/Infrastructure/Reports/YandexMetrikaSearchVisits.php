<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats;

final class YandexMetrikaSearchVisits
{
    /**
     * Сумма переходов из отчёта «Поисковые системы» за период.
     * Нулевые visits (строки целей в той же таблице) в сумму не входят.
     *
     * @param  list<YandexMetrikaSearchEnginesStats>  $stats
     * @return array{yandex: int, google: int, other: int, total: int}
     */
    public static function totals(array $stats): array
    {
        $yandex = 0;
        $google = 0;
        $other = 0;

        foreach ($stats as $item) {
            $visits = $item->getVisits();
            if ($visits <= 0) {
                continue;
            }

            match ($item->getSearchEngine()) {
                'yandex' => $yandex += $visits,
                'google' => $google += $visits,
                default => $other += $visits,
            };
        }

        return [
            'yandex' => $yandex,
            'google' => $google,
            'other' => $other,
            'total' => $yandex + $google + $other,
        ];
    }

    /**
     * % выполнения: факт / план × 100, как в Статистике. Нет плана или план 0 → пустая ячейка.
     */
    public static function performancePercent(int $fact, int $plan): string
    {
        if ($plan <= 0) {
            return '';
        }

        return ((int) round(($fact / $plan) * 100)).'%';
    }

    /**
     * @param  list<YandexMetrikaSearchEnginesStats>  $stats
     * @return array<string, string>
     */
    public static function tagValues(array $stats, int $plan = 0): array
    {
        $totals = self::totals($stats);
        $fact = $totals['total'];

        return [
            'ym.search_visits.yandex' => (string) $totals['yandex'],
            'ym.search_visits.google' => (string) $totals['google'],
            'ym.search_visits.other' => (string) $totals['other'],
            'ym.search_visits.total' => (string) $fact,
            'ym.search_visits.plan' => (string) $plan,
            'ym.search_visits.fact' => (string) $fact,
            'ym.search_visits.performance' => self::performancePercent($fact, $plan),
        ];
    }
}
