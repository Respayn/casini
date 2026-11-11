<?php

namespace Src\Domain\YandexMetrika;

final class SearchPhraseNormalizer
{
    public const MAX_LENGTH = 255;

    public static function normalize(string $phrase): string
    {
        $value = str_replace("\u{00A0}", ' ', $phrase);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');

        if (mb_strlen($value, 'UTF-8') > self::MAX_LENGTH) {
            return mb_substr($value, 0, self::MAX_LENGTH, 'UTF-8');
        }

        return $value;
    }
}
