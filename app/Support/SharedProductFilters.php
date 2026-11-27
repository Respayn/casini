<?php

namespace App\Support;

/**
 * Общие галочки продуктов (Каналы, Статистика, Планирование, Отчёты).
 * Живут в сессии: отметили на одном экране — на остальных уже стоят.
 */
class SharedProductFilters
{
    public const SHOW_INACTIVE_KEY = 'shared_product_filters.show_inactive';

    public const INCLUDE_VAT_KEY = 'shared_product_filters.include_vat';

    /**
     * Если в этой сессии галочку ещё не трогали, остаётся значение экрана и оно становится общим.
     */
    public static function resolveShowInactive(bool $pageValue): bool
    {
        return self::resolve(self::SHOW_INACTIVE_KEY, $pageValue);
    }

    public static function resolveIncludeVat(bool $pageValue): bool
    {
        return self::resolve(self::INCLUDE_VAT_KEY, $pageValue);
    }

    public static function rememberShowInactive(bool $value): void
    {
        session([self::SHOW_INACTIVE_KEY => $value]);
    }

    public static function rememberIncludeVat(bool $value): void
    {
        session([self::INCLUDE_VAT_KEY => $value]);
    }

    private static function resolve(string $key, bool $pageValue): bool
    {
        if (! session()->has($key)) {
            session([$key => $pageValue]);

            return $pageValue;
        }

        return (bool) session($key);
    }
}
