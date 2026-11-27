<?php

namespace App\Services\Bitrix24;

/**
 * Список работ из комментария Битрикс24: куски после спецсимвола 💪.
 */
final class Bitrix24WorkParser
{
    public const MARKER = '💪';

    public static function isChecklistComplete(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'Y' || $value === 'y';
    }

    /**
     * @return list<string>
     */
    public static function fromComment(string $html): array
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '' || ! str_contains($text, self::MARKER)) {
            return [];
        }

        $parts = explode(self::MARKER, $text);
        array_shift($parts);

        $items = [];

        foreach ($parts as $part) {
            $line = trim($part);

            if ($line !== '') {
                $items[] = $line;
            }
        }

        return $items;
    }
}
