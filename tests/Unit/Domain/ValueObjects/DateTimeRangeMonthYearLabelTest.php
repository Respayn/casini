<?php

namespace Tests\Unit\Domain\ValueObjects;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\DateTimeRange;

class DateTimeRangeMonthYearLabelTest extends TestCase
{
    public function test_same_month_is_written_as_month_and_year(): void
    {
        $period = new DateTimeRange(
            new DateTimeImmutable('2026-09-01'),
            new DateTimeImmutable('2026-09-30 23:59:59'),
        );

        $this->assertSame('сентябрь 2026', $period->monthYearLabel());
    }

    public function test_month_range_joins_start_and_end_with_hyphen(): void
    {
        $period = new DateTimeRange(
            new DateTimeImmutable('2026-09-01'),
            new DateTimeImmutable('2026-10-31'),
        );

        $this->assertSame('сентябрь 2026 - октябрь 2026', $period->monthYearLabel());
    }

    public function test_start_and_end_dates_are_written_in_words(): void
    {
        $period = new DateTimeRange(
            new DateTimeImmutable('2026-09-01'),
            new DateTimeImmutable('2026-09-30 23:59:59'),
        );

        $this->assertSame('1 сентября 2026', $period->startDateLabel());
        $this->assertSame('30 сентября 2026', $period->endDateLabel());
    }
}
