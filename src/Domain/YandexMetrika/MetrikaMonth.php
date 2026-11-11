<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Календарный месяц съёма Метрики (Y-m-d без сдвига из-за UTC в toArray()).
 */
final class MetrikaMonth
{
    public static function fromMixed(mixed $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return new DateTimeImmutable($value->format('Y-m-d'));
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            throw new \InvalidArgumentException('Пустая дата месяца Метрики');
        }

        if (str_contains($raw, 'T') || str_contains($raw, 'Z')) {
            $utc = new DateTimeImmutable($raw);
            $local = $utc->setTimezone(new DateTimeZone(date_default_timezone_get()));

            return new DateTimeImmutable($local->format('Y-m-d'));
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $matches) === 1) {
            return new DateTimeImmutable($matches[1]);
        }

        return new DateTimeImmutable($raw);
    }
}
