<?php

namespace App\Helpers;

class PhraseDuplicateHelper
{
    public static function normalize(string $phrase): string
    {
        return mb_strtolower(trim($phrase));
    }

    /**
     * @param  array<int, array{code?: mixed, phrases?: string[]}>  $regions
     * @return array<int, string>
     */
    public static function duplicateKeys(array $regions): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($regions as $region) {
            foreach ($region['phrases'] ?? [] as $phrase) {
                $normalized = self::normalize($phrase);

                if ($normalized === '') {
                    continue;
                }

                if (isset($seen[$normalized])) {
                    $duplicates[$normalized] = $normalized;
                } else {
                    $seen[$normalized] = true;
                }
            }
        }

        return array_values($duplicates);
    }

    /**
     * @param  array<int, array{code?: mixed, phrases?: string[]}>  $regions
     */
    public static function isValidForSave(array $regions): bool
    {
        if ($regions === []) {
            return false;
        }

        if (self::duplicateKeys($regions) !== []) {
            return false;
        }

        foreach ($regions as $region) {
            $code = $region['code'] ?? null;

            if ($code === null || $code === '') {
                return false;
            }

            $phrases = $region['phrases'] ?? [];

            if ($phrases === []) {
                return false;
            }

            foreach ($phrases as $phrase) {
                if (trim($phrase) === '') {
                    return false;
                }
            }
        }

        return true;
    }
}
