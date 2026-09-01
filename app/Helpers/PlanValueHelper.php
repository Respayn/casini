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

    private static function formatNumber(float $value): string
    {
        return Number::format($value, precision: 0, locale: 'ru');
    }
}
