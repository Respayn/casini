<?php

namespace Src\Domain\YandexMetrika;

class YandexMetrikaUtmFilterBuilder
{
    private const DIMENSIONS = [
        'source' => 'ym:s:UTMSource',
        'medium' => 'ym:s:UTMMedium',
        'campaign' => 'ym:s:UTMCampaign',
    ];

    /**
     * Build a Reporting API filter expression for the active UTM dimension.
     *
     * Empty $value → "dimension is not empty".
     * Comma-separated values → OR with contains/wildcard operators.
     */
    public function build(string $mode, string $value = ''): ?string
    {
        $dimension = self::DIMENSIONS[$mode] ?? null;
        if ($dimension === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $dimension . "!=''";
        }

        $parts = array_filter(array_map('trim', explode(',', $trimmed)), fn (string $v) => $v !== '');
        if ($parts === []) {
            return $dimension . "!=''";
        }

        $conditions = array_map(
            fn (string $v) => $this->condition($dimension, $v),
            $parts
        );

        return count($conditions) === 1
            ? $conditions[0]
            : '(' . implode(' OR ', $conditions) . ')';
    }

    private function condition(string $dimension, string $value): string
    {
        $operator = str_contains($value, '*') ? '=*' : '=@';

        return $dimension . $operator . "'" . addcslashes($value, "\\'") . "'";
    }
}
