<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\YandexMetrika\YandexMetrikaVisitsSearchQueries;

final class YandexMetrikaSearchQueries
{
    public const PHRASE_KEY = 'ym.search_queries.phrase';

    public const VISITS_KEY = 'ym.search_queries.visits';

    public static function isTag(string $variable): bool
    {
        return str_starts_with($variable, 'ym.search_queries.');
    }

    /**
     * Строки «фраза / число переходов» за период. Нулевые строки не входят.
     *
     * @param  list<YandexMetrikaVisitsSearchQueries>  $stats
     * @return list<array{phrase: string, visits: int}>
     */
    public static function rows(array $stats, bool $useVisitors = false): array
    {
        $aggregated = [];

        foreach ($stats as $item) {
            $phrase = $item->getPhrase();
            if ($phrase === '') {
                continue;
            }

            $value = $useVisitors ? $item->getVisitors() : $item->getVisits();
            if ($value <= 0) {
                continue;
            }

            $aggregated[$phrase] = ($aggregated[$phrase] ?? 0) + $value;
        }

        arsort($aggregated);

        $rows = [];
        foreach ($aggregated as $phrase => $visits) {
            $rows[] = [
                'phrase' => $phrase,
                'visits' => $visits,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<YandexMetrikaVisitsSearchQueries>  $stats
     * @return list<array{phrase: string, position: string}>
     */
    public static function pairRows(array $stats, bool $useVisitors = false): array
    {
        return array_map(
            static fn (array $row): array => [
                'phrase' => $row['phrase'],
                'position' => (string) $row['visits'],
            ],
            self::rows($stats, $useVisitors),
        );
    }
}
