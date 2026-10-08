<?php

namespace Src\Domain\YandexMetrika;

class YandexMetrikaUtmFilterBuilder
{
    /**
     * UTM-измерения под выбранную модель атрибуции (как отчёт «Метки UTM» / preset tags_u_t_m).
     */
    private const DIMENSIONS = [
        'source' => 'ym:s:<attribution>UTMSource',
        'medium' => 'ym:s:<attribution>UTMMedium',
        'campaign' => 'ym:s:<attribution>UTMCampaign',
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
            return $dimension."!=''";
        }

        $parts = array_filter(array_map('trim', explode(',', $trimmed)), fn (string $v) => $v !== '');
        if ($parts === []) {
            return $dimension."!=''";
        }

        $conditions = array_map(
            fn (string $v) => $this->condition($dimension, $v),
            $parts
        );

        return count($conditions) === 1
            ? $conditions[0]
            : '('.implode(' OR ', $conditions).')';
    }

    /**
     * API-измерение для группировки (то же, что в фильтре).
     */
    public function dimension(string $mode): ?string
    {
        return self::DIMENSIONS[$mode] ?? null;
    }

    private function condition(string $dimension, string $value): string
    {
        $operator = str_contains($value, '*') ? '=*' : '=@';

        return $dimension.$operator."'".addcslashes($value, "\\'")."'";
    }
}
