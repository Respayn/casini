<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\YandexMetrika\YandexMetrikaVisitsGeo;

final class YandexMetrikaGeoVisits
{
    public const CITY_KEY = 'ym.visits_geo.city';

    public const VISITS_KEY = 'ym.visits_geo.visits';

    public static function isTag(string $variable): bool
    {
        return str_starts_with($variable, 'ym.visits_geo.');
    }

    /**
     * Строки «город / число переходов» за период. Нулевые строки не входят.
     *
     * @param  list<YandexMetrikaVisitsGeo>  $stats
     * @return list<array{city: string, visits: int}>
     */
    public static function rows(array $stats, bool $useVisitors = false): array
    {
        $aggregated = [];

        foreach ($stats as $item) {
            $city = $item->getCity();
            if ($city === '') {
                continue;
            }

            $value = $useVisitors ? $item->getVisitors() : $item->getVisits();
            if ($value <= 0) {
                continue;
            }

            $aggregated[$city] = ($aggregated[$city] ?? 0) + $value;
        }

        arsort($aggregated);

        $rows = [];
        foreach ($aggregated as $city => $visits) {
            $rows[] = [
                'city' => $city,
                'visits' => $visits,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<YandexMetrikaVisitsGeo>  $stats
     * @return list<array{phrase: string, position: string}>
     */
    public static function pairRows(array $stats, bool $useVisitors = false): array
    {
        return array_map(
            static fn (array $row): array => [
                'phrase' => $row['city'],
                'position' => (string) $row['visits'],
            ],
            self::rows($stats, $useVisitors),
        );
    }
}
