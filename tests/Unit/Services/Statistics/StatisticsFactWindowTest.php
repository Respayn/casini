<?php

namespace Tests\Unit\Services\Statistics;

use App\Domain\Statistics\Enums\StatisticsReportDetailLevel;
use App\Services\Statistics\StatisticsFactWindow;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatisticsFactWindowTest extends TestCase
{
    #[Test]
    public function without_accumulate_keeps_bucket_bounds(): void
    {
        $gridMonth = Carbon::parse('2026-10-01');
        $periodFrom = Carbon::parse('2026-10-01');
        $bucketFrom = Carbon::parse('2026-10-15');
        $bucketTo = Carbon::parse('2026-10-15');

        [$from, $to] = StatisticsFactWindow::resolve(
            $bucketFrom,
            $bucketTo,
            StatisticsReportDetailLevel::BY_DAY,
            $gridMonth,
            $periodFrom,
            accumulate: false,
        );

        $this->assertSame('2026-10-15', $from->toDateString());
        $this->assertSame('2026-10-15', $to->toDateString());
    }

    #[Test]
    public function accumulate_day_starts_from_month_first(): void
    {
        $gridMonth = Carbon::parse('2026-10-01');
        $periodFrom = Carbon::parse('2026-10-01');
        $bucketFrom = Carbon::parse('2026-10-15');
        $bucketTo = Carbon::parse('2026-10-15');

        [$from, $to] = StatisticsFactWindow::resolve(
            $bucketFrom,
            $bucketTo,
            StatisticsReportDetailLevel::BY_DAY,
            $gridMonth,
            $periodFrom,
            accumulate: true,
        );

        $this->assertSame('2026-10-01', $from->toDateString());
        $this->assertSame('2026-10-15', $to->toDateString());
    }

    #[Test]
    public function accumulate_week_starts_from_month_first(): void
    {
        $gridMonth = Carbon::parse('2026-10-01');
        $periodFrom = Carbon::parse('2026-10-01');
        // вторая неделя октября 2026: 05.10 - 11.10
        $bucketFrom = Carbon::parse('2026-10-05');
        $bucketTo = Carbon::parse('2026-10-11');

        [$from, $to] = StatisticsFactWindow::resolve(
            $bucketFrom,
            $bucketTo,
            StatisticsReportDetailLevel::BY_WEEK,
            $gridMonth,
            $periodFrom,
            accumulate: true,
        );

        $this->assertSame('2026-10-01', $from->toDateString());
        $this->assertSame('2026-10-11', $to->toDateString());
    }

    #[Test]
    public function accumulate_month_starts_from_period_first_month(): void
    {
        $gridMonth = Carbon::parse('2026-11-01');
        $periodFrom = Carbon::parse('2026-09-01');
        $bucketFrom = Carbon::parse('2026-11-01');
        $bucketTo = Carbon::parse('2026-11-30');

        [$from, $to] = StatisticsFactWindow::resolve(
            $bucketFrom,
            $bucketTo,
            StatisticsReportDetailLevel::BY_MONTH,
            $gridMonth,
            $periodFrom,
            accumulate: true,
        );

        $this->assertSame('2026-09-01', $from->toDateString());
        $this->assertSame('2026-11-30', $to->toDateString());
    }

    #[Test]
    public function without_accumulate_month_keeps_own_month(): void
    {
        $gridMonth = Carbon::parse('2026-11-01');
        $periodFrom = Carbon::parse('2026-09-01');
        $bucketFrom = Carbon::parse('2026-11-01');
        $bucketTo = Carbon::parse('2026-11-30');

        [$from, $to] = StatisticsFactWindow::resolve(
            $bucketFrom,
            $bucketTo,
            StatisticsReportDetailLevel::BY_MONTH,
            $gridMonth,
            $periodFrom,
            accumulate: false,
        );

        $this->assertSame('2026-11-01', $from->toDateString());
        $this->assertSame('2026-11-30', $to->toDateString());
    }
}
