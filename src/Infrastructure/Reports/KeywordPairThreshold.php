<?php

namespace Src\Infrastructure\Reports;

final class KeywordPairThreshold
{
    /**
     * @param  list<string>  $variables
     * @return array{phraseKey: string, positionKey: string, min: int|null}
     */
    public static function resolve(string $phraseKey, string $positionKey, array $variables): array
    {
        $resolvedPhrase = $phraseKey;
        $resolvedPosition = $positionKey;
        $min = null;

        foreach ($variables as $variable) {
            $parsed = self::parse($variable, $phraseKey);
            if ($parsed !== null) {
                $resolvedPhrase = $variable;
                if ($parsed['min'] !== null) {
                    $min = $parsed['min'];
                }
            }

            $parsed = self::parse($variable, $positionKey);
            if ($parsed !== null) {
                $resolvedPosition = $variable;
                if ($parsed['min'] !== null) {
                    $min = $parsed['min'];
                }
            }
        }

        return [
            'phraseKey' => $resolvedPhrase,
            'positionKey' => $resolvedPosition,
            'min' => $min,
        ];
    }

    /**
     * @return array{min: int|null}|null
     */
    public static function parse(string $variable, string $baseKey): ?array
    {
        if ($variable === $baseKey) {
            return ['min' => null];
        }

        if (preg_match('/^'.preg_quote($baseKey, '/').'\[(\d+)\]$/', $variable, $matches) === 1) {
            return ['min' => (int) $matches[1]];
        }

        return null;
    }

    /**
     * @param  list<array{phrase: string, position: string}>  $rows
     * @return list<array{phrase: string, position: string}>
     */
    public static function filterRows(array $rows, ?int $min): array
    {
        if ($min === null) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => is_numeric($row['position']) && (int) $row['position'] >= $min
        ));
    }
}
