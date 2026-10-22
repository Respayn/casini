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
        return ['region', 'table', 'top_3', 'top_5', 'top_10'];
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
}
