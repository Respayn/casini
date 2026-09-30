<?php

namespace App\Helpers;

use Illuminate\Support\Number;

class PlanValueHelper
{
    /**
     * Форматирование планового значения для отчётов (Каналы, Статистика).
     * Числа округляются до целых.
     */
    public static function format(mixed $value, ?string $format): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $numeric = (float) round((float) $value);

        return match ($format) {
            'currency' => Number::currency(
                $numeric,
                in: 'RUB',
                locale: 'ru',
                precision: 0,
            ),
            'percent' => self::formatNumber($numeric).'%',
            default => self::formatNumber($numeric),
        };
    }

    /**
     * @return array{value: string, suffix: ?string}
     */
    public static function planColumnParts(
        mixed $value,
        ?string $format,
        ?string $parameterCode = null,
        bool $showPrimarySuffix = false,
    ): array {
        $formatted = self::format($value, $format);
        if ($formatted === '-') {
            return ['value' => '-', 'suffix' => null];
        }

        if ($showPrimarySuffix) {
            $label = PrimaryParameterPlanHelper::label($parameterCode);
            if ($label !== null) {
                return ['value' => $formatted, 'suffix' => $label];
            }
        }

        return ['value' => $formatted, 'suffix' => null];
    }

    /**
     * Плановое значение с подписью основного параметра в скобках (Каналы, Статистика).
     */
    public static function formatForPlanColumn(
        mixed $value,
        ?string $format,
        ?string $parameterCode = null,
        bool $showPrimarySuffix = false,
    ): string {
        $parts = self::planColumnParts($value, $format, $parameterCode, $showPrimarySuffix);

        return $parts['suffix'] !== null
            ? $parts['value'].' '.$parts['suffix']
            : $parts['value'];
    }

    private static function formatNumber(float $value): string
    {
        return Number::format($value, precision: 0, locale: 'ru');
    }
}
