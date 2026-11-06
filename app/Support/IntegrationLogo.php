<?php

namespace App\Support;

/**
 * Один набор значков интеграций для Каналов и Статистики.
 */
class IntegrationLogo
{
    public static function component(string $code): string
    {
        return match ($code) {
            'yandex_direct' => 'yandex-direct',
            'yandex_metrika' => 'yandex-metrika',
            'callibri' => 'callibri',
            'yandex_search_api' => 'yandex-search-api',
            'google_sheets' => 'google-sheets',
            'bitrix24' => 'bitrix24',
            default => 'default',
        };
    }
}
