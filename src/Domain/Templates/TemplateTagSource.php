<?php

namespace Src\Domain\Templates;

/**
 * Источник данных тега шаблона отчета: определяется по префиксу тега.
 * Значение совпадает с именем логотипа в resources/views/components/icons/logo.
 */
enum TemplateTagSource: string
{
    case CASINI = 'casini';
    case CALLIBRI = 'callibri';
    case YANDEX_SEARCH_API = 'yandex-search-api';
    case YANDEX_DIRECT = 'yandex-direct';
    case YANDEX_METRIKA = 'yandex-metrika';
    case BITRIX24 = 'bitrix24';
    case UNKNOWN = 'default';

    private const PREFIXES = [
        'callibri.' => self::CALLIBRI,
        'yandex_search.' => self::YANDEX_SEARCH_API,
        'yd.' => self::YANDEX_DIRECT,
        'ym.' => self::YANDEX_METRIKA,
        'bitrix24.' => self::BITRIX24,
        'current_year' => self::CASINI,
        'period_' => self::CASINI,
        'project_' => self::CASINI,
        'manager_' => self::CASINI,
        'agency_' => self::CASINI,
    ];

    public static function fromTag(string $tag): self
    {
        foreach (self::PREFIXES as $prefix => $source) {
            if (str_starts_with($tag, $prefix)) {
                return $source;
            }
        }

        return self::UNKNOWN;
    }

    public function label(): string
    {
        return match ($this) {
            self::CASINI => 'Касини',
            self::CALLIBRI => 'Callibri',
            self::YANDEX_SEARCH_API => 'Yandex Search API',
            self::YANDEX_DIRECT => 'Яндекс Директ',
            self::YANDEX_METRIKA => 'Яндекс Метрика',
            self::BITRIX24 => 'Битрикс24',
            self::UNKNOWN => 'Источник не определен',
        };
    }
}
