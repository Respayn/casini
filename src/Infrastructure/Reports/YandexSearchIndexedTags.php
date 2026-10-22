<?php

namespace Src\Infrastructure\Reports;

use App\Enums\SearchRegion;

final class YandexSearchIndexedTags
{
    /**
     * @return list<string>
     */
    public static function fields(): array
    {
        return ['region', 'phrase', 'position', 'top_3', 'top_5', 'top_10'];
    }

    public static function key(string $field, int $index): string
    {
        return 'yandex_search.'.$field.'['.$index.']';
    }

    /**
     * @param  list<mixed>  $regions
     * @return list<string>
     */
    public static function keysForRegions(array $regions): array
    {
        $keys = [];

        foreach (self::numberedRegions($regions) as $region) {
            foreach (self::fields() as $field) {
                $keys[] = self::key($field, $region['index']);
            }
        }

        return $keys;
    }

    public static function isSearchApiTag(string $variable): bool
    {
        return str_starts_with($variable, 'yandex_search.');
    }

    /**
     * @param  list<mixed>  $regions
     * @return list<array{index: int, code: int, label: string}>
     */
    public static function numberedRegions(array $regions): array
    {
        $result = [];
        $index = 1;

        foreach ($regions as $region) {
            if (! is_array($region) || ! is_numeric($region['code'] ?? null)) {
                continue;
            }

            $code = (int) $region['code'];
            if ($code <= 0) {
                continue;
            }

            $result[] = [
                'index' => $index,
                'code' => $code,
                'label' => SearchRegion::tryFrom($code)?->label() ?? '',
            ];
            $index++;
        }

        return $result;
    }

    /**
     * Доли фраз таблицы в топ-3, топ-5 и топ-10. Знаменатель: число строк.
     *
     * @param  list<mixed>  $positions
     * @return array{top_3: float, top_5: float, top_10: float}
     */
    public static function topShares(array $positions): array
    {
        $total = count($positions);
        if ($total === 0) {
            return [
                'top_3' => 0.0,
                'top_5' => 0.0,
                'top_10' => 0.0,
            ];
        }

        $top3 = 0;
        $top5 = 0;
        $top10 = 0;

        foreach ($positions as $position) {
            if (self::isRank($position, 3)) {
                $top3++;
            }
            if (self::isRank($position, 5)) {
                $top5++;
            }
            if (self::isRank($position, 10)) {
                $top10++;
            }
        }

        return [
            'top_3' => round($top3 / $total * 100, 1),
            'top_5' => round($top5 / $total * 100, 1),
            'top_10' => round($top10 / $total * 100, 1),
        ];
    }

    public static function formatShare(float $percent): string
    {
        return round($percent, 1).'%';
    }

    private static function isRank(mixed $position, int $limit): bool
    {
        if (! is_numeric($position)) {
            return false;
        }

        $value = (int) $position;

        return $value >= 1 && $value <= $limit;
    }
}
