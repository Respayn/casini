<?php

namespace Tests\Unit\Domain\Reports;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Domain\Reports\ReportColumnGrouping;
use Src\Domain\Reports\ReportDateSort;
use Src\Domain\Reports\ReportPeriodBuckets;
use Src\Domain\ValueObjects\DateTimeRange;

class ReportPeriodBucketsTest extends TestCase
{
    public function test_single_month_does_not_expand(): void
    {
        $buckets = ReportPeriodBuckets::make($this->period('2026-10-01', '2026-10-31'));

        $this->assertFalse($buckets->expandColumns);
        $this->assertSame('2026-10', $buckets->reportingMonthKey);
        $this->assertSame('октябрь 2026', $buckets->reportingMonth->monthYearLabel());
        $this->assertSame(['Окт 2026'], $buckets->labels());
    }

    public function test_months_newest_first(): void
    {
        $buckets = ReportPeriodBuckets::make($this->period('2026-03-01', '2026-10-31'));

        $this->assertTrue($buckets->expandColumns);
        $this->assertSame(
            ['Окт 2026', 'Сен 2026', 'Авг 2026', 'Июл 2026', 'Июн 2026', 'Май 2026', 'Апр 2026', 'Мар 2026'],
            $buckets->labels(),
        );
        $this->assertSame('октябрь 2026', $buckets->reportingMonth->monthYearLabel());
    }

    public function test_months_oldest_first(): void
    {
        $buckets = ReportPeriodBuckets::make(
            $this->period('2026-03-01', '2026-05-31'),
            ReportColumnGrouping::Months,
            ReportDateSort::OldestFirst,
        );

        $this->assertSame(['Мар 2026', 'Апр 2026', 'Май 2026'], $buckets->labels());
    }

    public function test_quarters_use_calendar_quarters_inside_period(): void
    {
        $buckets = ReportPeriodBuckets::make(
            $this->period('2026-03-01', '2026-10-31'),
            ReportColumnGrouping::Quarters,
            ReportDateSort::NewestFirst,
        );

        $this->assertSame(
            ['4 кв. 2026', '3 кв. 2026', '2 кв. 2026', '1 кв. 2026'],
            $buckets->labels(),
        );
    }

    public function test_years_use_year_labels(): void
    {
        $buckets = ReportPeriodBuckets::make(
            $this->period('2025-11-01', '2026-02-28'),
            ReportColumnGrouping::Years,
            ReportDateSort::OldestFirst,
        );

        $this->assertSame(['2025', '2026'], $buckets->labels());
    }

    public function test_series_sums_visits_and_keeps_last_month_for_percents(): void
    {
        $buckets = ReportPeriodBuckets::make(
            $this->period('2026-09-01', '2026-10-31'),
            ReportColumnGrouping::Months,
            ReportDateSort::NewestFirst,
        );

        $this->assertSame(
            ['8', '4'],
            $buckets->seriesFromMonthly(['2026-09' => 4, '2026-10' => 8], '0', true),
        );
        $this->assertSame(
            ['70%', '40%'],
            $buckets->seriesFromMonthly(['2026-09' => '40%', '2026-10' => '70%'], '-', false),
        );
        $this->assertSame(
            ['-', '3'],
            $buckets->seriesFromMonthly(['2026-09' => 3], '-', false),
        );
    }

    public function test_quarter_series_sums_only_months_inside_bucket(): void
    {
        $buckets = ReportPeriodBuckets::make(
            $this->period('2026-03-01', '2026-10-31'),
            ReportColumnGrouping::Quarters,
            ReportDateSort::OldestFirst,
        );

        $this->assertSame(
            ['1', '6', '15', '8'],
            $buckets->seriesFromMonthly([
                '2026-03' => 1,
                '2026-04' => 2,
                '2026-06' => 4,
                '2026-07' => 7,
                '2026-09' => 8,
                '2026-10' => 8,
            ], '0', true),
        );
    }

    public function test_series_total_ignores_months_outside_period(): void
    {
        $buckets = ReportPeriodBuckets::make(
            $this->period('2026-09-01', '2026-10-31'),
            ReportColumnGrouping::Months,
            ReportDateSort::NewestFirst,
        );

        $this->assertSame(
            ['8', '4'],
            $buckets->seriesFromMonthly([
                '2026-08' => 100,
                '2026-09' => 4,
                '2026-10' => 8,
            ], '0', true),
        );
    }

    private function period(string $start, string $end): DateTimeRange
    {
        return new DateTimeRange(
            new DateTimeImmutable($start),
            new DateTimeImmutable($end),
        );
    }
}
