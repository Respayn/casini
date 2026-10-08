<?php

namespace App\Support;

class YandexMetrikaSearchEngine
{
    public const YANDEX = 'yandex';

    public const GOOGLE = 'google';

    public const OTHER = 'other';

    /**
     * Нормализует название поисковой системы из Reporting API в ключ таблицы.
     */
    public static function normalize(?string $name): string
    {
        $value = mb_strtolower(trim((string) $name));

        if ($value === '') {
            return self::OTHER;
        }

        if (str_contains($value, 'yandex') || str_contains($value, 'яндекс')) {
            return self::YANDEX;
        }

        if (str_contains($value, 'google') || str_contains($value, 'гугл')) {
            return self::GOOGLE;
        }

        return self::OTHER;
    }
}
