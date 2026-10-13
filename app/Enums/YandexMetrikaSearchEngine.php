<?php

namespace App\Enums;

/**
 * Ключ поисковой системы в таблице целей отчёта «Поисковые системы».
 */
enum YandexMetrikaSearchEngine: string
{
    case YANDEX = 'yandex';
    case GOOGLE = 'google';
    case OTHER = 'other';

    /**
     * Название поисковой системы из Reporting API.
     */
    public static function fromApiName(?string $name): self
    {
        $value = mb_strtolower(trim((string) $name));

        if (str_contains($value, 'yandex') || str_contains($value, 'яндекс')) {
            return self::YANDEX;
        }

        if (str_contains($value, 'google') || str_contains($value, 'гугл')) {
            return self::GOOGLE;
        }

        return self::OTHER;
    }
}
