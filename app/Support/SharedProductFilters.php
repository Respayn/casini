<?php

namespace App\Support;

use App\Enums\ChannelReportGrouping;

/**
 * Общие галочки и группировка продуктов (Каналы, Статистика, Планирование, Отчёты).
 * Живут в сессии: отметили на одном экране — на остальных уже стоят.
 */
class SharedProductFilters
{
    public const SHOW_INACTIVE_KEY = 'shared_product_filters.show_inactive';

    public const INCLUDE_VAT_KEY = 'shared_product_filters.include_vat';

    public const GROUPING_KEY = 'shared_product_filters.grouping';

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

    /**
     * Общая группировка отчётов: none | clients | project_type.
     * Если в сессии ещё нет - берём значение экрана и запоминаем.
     */
    public static function resolveGrouping(string $pageValue): string
    {
        $pageValue = self::normalizeGrouping($pageValue);

        if (! session()->has(self::GROUPING_KEY)) {
            session([self::GROUPING_KEY => $pageValue]);

            return $pageValue;
        }

        return self::normalizeGrouping((string) session(self::GROUPING_KEY));
    }

    public static function rememberShowInactive(bool $value): void
    {
        session([self::SHOW_INACTIVE_KEY => $value]);
    }

    public static function rememberIncludeVat(bool $value): void
    {
        session([self::INCLUDE_VAT_KEY => $value]);
    }

    public static function rememberGrouping(string $value): void
    {
        session([self::GROUPING_KEY => self::normalizeGrouping($value)]);
    }

    public static function normalizeGrouping(string $value): string
    {
        return match ($value) {
            ChannelReportGrouping::CLIENTS->value,
            ChannelReportGrouping::PROJECT_TYPE->value => $value,
            default => ChannelReportGrouping::NONE->value,
        };
    }

    /**
     * Поле колонки, которую при активной группировке держим первой и видимой.
     *
     * @param  string  $projectTypeField  Ключ колонки типа (Каналы/Статистика: project-type, Отчёты: channel)
     */
    public static function pinnedColumnField(
        string $grouping,
        string $clientField = 'client',
        string $projectTypeField = 'project-type',
    ): ?string {
        return match (self::normalizeGrouping($grouping)) {
            ChannelReportGrouping::CLIENTS->value => $clientField,
            ChannelReportGrouping::PROJECT_TYPE->value => $projectTypeField,
            default => null,
        };
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
