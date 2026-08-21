<?php

namespace Src\Domain\YandexMetrika;

/**
 * Парсинг минус-фраз и фильтр API для отчёта «Поисковые запросы».
 */
class SearchQueriesMinusList
{
    /**
     * Измерение API под выбранную модель атрибуции (как в отчёте «Поисковые запросы»).
     */
    public const SEARCH_PHRASE_DIMENSION = 'ym:s:<attribution>SearchPhrase';

    /**
     * @return list<string>
     */
    public static function parse(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $result = [];

        foreach ($lines as $line) {
            $name = trim($line);
            if ($name !== '') {
                $result[] = $name;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Фильтр Reporting API: исключить фразы по вхождению (AND).
     * Пустой список — без доп. фильтра.
     */
    public static function buildFilter(string $text): ?string
    {
        $phrases = self::parse($text);
        if ($phrases === []) {
            return null;
        }

        $dimension = self::SEARCH_PHRASE_DIMENSION;
        $parts = array_map(
            static fn (string $phrase): string => $dimension."!@'".self::escapeValue($phrase)."'",
            $phrases
        );

        return count($parts) === 1
            ? $parts[0]
            : '('.implode(' AND ', $parts).')';
    }

    private static function escapeValue(string $value): string
    {
        return addcslashes($value, "\\'");
    }
}
