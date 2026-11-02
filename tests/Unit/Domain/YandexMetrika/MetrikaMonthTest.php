<?php

namespace Tests\Unit\Domain\YandexMetrika;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\MetrikaMonth;

class MetrikaMonthTest extends TestCase
{
    public function test_keeps_calendar_day_from_date_string(): void
    {
        $month = MetrikaMonth::fromMixed('2026-10-01');

        $this->assertSame('2026-10-01', $month->format('Y-m-d'));
    }

    public function test_restores_calendar_day_from_utc_iso_midnight(): void
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');

        try {
            $month = MetrikaMonth::fromMixed('2026-09-30T21:00:00.000000Z');
            $this->assertSame('2026-10-01', $month->format('Y-m-d'));
        } finally {
            date_default_timezone_set($previous);
        }
    }

    public function test_uses_calendar_day_from_datetime(): void
    {
        $value = new DateTimeImmutable('2026-10-01 00:00:00', new DateTimeZone('Europe/Moscow'));

        $this->assertSame('2026-10-01', MetrikaMonth::fromMixed($value)->format('Y-m-d'));
    }
}
