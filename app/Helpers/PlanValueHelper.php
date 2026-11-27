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

        return match ($format) {
            'currency' => MoneyDisplay::currency($value),
            'percent' => self::formatNumber((float) round((float) $value)).'%',
            default => self::formatNumber((float) round((float) $value)),
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

    private static function formatNumber(float $value): string
    {
        return Number::format($value, precision: 0, locale: 'ru');
    }
}
