<?php

namespace App\Helpers;

use App\Support\SharedProductFilters;
use Illuminate\Support\Number;

/**
 * Показ денег в Каналах и Статистике с учётом «Округлять копейки?».
 */
class MoneyDisplay
{
    public static function currency(mixed $value): string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '-';
        }

        $amount = (float) $value;

        if (SharedProductFilters::roundKopecks()) {
            return Number::currency(
                (float) round($amount),
                in: 'RUB',
                locale: 'ru',
                precision: 0,
            );
        }

        return Number::currency(
            $amount,
            in: 'RUB',
            locale: 'ru',
            precision: 2,
        );
    }
}
