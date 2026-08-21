<?php

namespace Src\Domain\YandexMetrika;

/**
 * Одноразовая миграция legacy textarea «search_engines_display» → root-ID Метрики.
 */
class SearchEnginesDisplayList
{
    /**
     * Корневая ПС под выбранную модель атрибуции (как в отчёте «Поисковые системы»).
     */
    public const SEARCH_ENGINE_ROOT_DIMENSION = 'ym:s:<attribution>SearchEngineRoot';

    /**
     * Детальная ПС под атрибуцию (лист дерева; для целей используем Root).
     */
    public const SEARCH_ENGINE_DIMENSION = 'ym:s:<attribution>SearchEngine';

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
     * Преобразует строки из старого textarea в канонические root-ID Метрики.
     *
     * @return list<string>
     */
    public static function migrateDisplayTextToIds(string $text): array
    {
        $ids = [];

        foreach (self::parse($text) as $name) {
            $id = self::mapDisplayNameToRootId($name);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function mapDisplayNameToRootId(string $name): ?string
    {
        $normalized = mb_strtolower(trim($name));

        if ($normalized === '') {
            return null;
        }

        return match (true) {
            $normalized === 'yandex'
                || $normalized === 'яндекс'
                || str_contains($normalized, 'yandex')
                || str_contains($normalized, 'яндекс') => 'yandex',
            $normalized === 'google'
                || $normalized === 'гугл'
                || str_contains($normalized, 'google')
                || str_contains($normalized, 'гугл') => 'google',
            $normalized === 'bing' || str_contains($normalized, 'bing') => 'bing',
            $normalized === 'mail'
                || $normalized === 'mail.ru'
                || str_contains($normalized, 'mail') => 'mail',
            $normalized === 'yahoo' || str_contains($normalized, 'yahoo') => 'yahoo',
            $normalized === 'rambler' || str_contains($normalized, 'rambler') => 'rambler',
            $normalized === 'duckduckgo' || str_contains($normalized, 'duckduckgo') => 'duckduckgo',
            default => null,
        };
    }

    /**
     * Фильтр API для корневых ПС. null = без доп. фильтра (все ПС).
     *
     * @param  list<string>  $ids
     */
    public static function buildSearchEngineRootFilter(bool $all, array $ids): ?string
    {
        if ($all) {
            return null;
        }

        $normalized = [];
        foreach ($ids as $id) {
            $value = trim((string) $id);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));
        if ($normalized === []) {
            return null;
        }

        $dimension = self::SEARCH_ENGINE_ROOT_DIMENSION;
        $parts = array_map(
            static fn (string $id): string => $dimension."=@'".str_replace("'", "\\'", $id)."'",
            $normalized
        );

        return count($parts) === 1
            ? $parts[0]
            : '('.implode(' OR ', $parts).')';
    }
}
