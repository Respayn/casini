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
     * @param  list<YandexMetrikaSearchEnginesStats>  $stats
     * @return array<string, string>
     */
    public static function tagValues(array $stats): array
    {
        $totals = self::totals($stats);

        return [
            'ym.search_visits.yandex' => (string) $totals['yandex'],
            'ym.search_visits.google' => (string) $totals['google'],
            'ym.search_visits.other' => (string) $totals['other'],
            'ym.search_visits.total' => (string) $totals['total'],
        ];
    }
}
