<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class StringHelper
{
    /**
     * Проверяет содержит ли строка хотя бы один из паттернов после нормализации
     */
    public static function containsAnyNormalized(
        string $value,
        array $patterns
    ): bool {
        $normalizedValue = self::normalize($value);

        foreach ($patterns as $pattern) {
            $normalizedPattern = self::normalize($pattern);
            if (str_contains($normalizedValue, $normalizedPattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверяет совпадение строки с любым из паттернов
     */
    public static function matchesAnyPattern(
        string $value,
        array $patterns
    ): bool {
        $normalizedValue = self::normalize($value);

        foreach ($patterns as $pattern) {
            $normalizedPattern = self::normalize($pattern);

            // Поддержка wildcard
            if (str_contains($normalizedPattern, '*')) {
                $regex = '/^' . str_replace('\*', '.*', preg_quote($normalizedPattern, '/')) . '$/';
                if (preg_match($regex, $normalizedValue)) {
                    return true;
                }
            } elseif ($normalizedValue === $normalizedPattern) {
                return true;
            }
        }
        return false;
    }

    /**
     * Нормализует строку для сравнения:
     * - Приводит к нижнему регистру
     * - Удаляет все не буквенно-цифровые символы
     */
    private static function normalize(string $input): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($input));
    }

    /**
     * Нормализует номер договора для поиска:
     * - Приводит к нижнему регистру
     * - Удаляет все неалфавитно-цифровые символы
     * - Обрезает пробелы
     */
    public static function normalizeContractNumber(string $number): string
    {
        return Str::lower(Str::slug(trim($number), ''));
    }

    /**
     * Нормализует дополнительный номер договора:
     * - Приводит к нижнему регистру
     * - Обрезает пробелы
     * - Возвращает пустую строку для null
     */
    public static function normalizeAdditionalNumber(?string $number): string
    {
        return trim(Str::lower($number ?? ''));
    }
}
