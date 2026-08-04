<?php

namespace Tests\Unit\Data\Statistics;

use App\Data\Statistics\StatisticsReportQueryData;
use App\Domain\Statistics\Enums\StatisticsReportDetailLevel;
use App\Enums\ChannelReportGrouping;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatisticsReportQueryDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_create_defaults_both_ends_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 15:00:00'));

        $data = StatisticsReportQueryData::create();

        $this->assertSame('2026-08-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-08-01', $data->dateTo->toDateString());
        $this->assertTrue($data->isSingleMonthPeriod());
        $this->assertSame(StatisticsReportDetailLevel::BY_WEEK, $data->detailLevel);
    }

    public function test_clamp_period_blocks_future_and_swaps_inverted_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = StatisticsReportQueryData::create();
        $data->dateFrom = Carbon::parse('2026-10-01');
        $data->dateTo = Carbon::parse('2026-09-01');
        $data->clampPeriodToPresent();

        $this->assertSame('2026-08-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-08-01', $data->dateTo->toDateString());
    }

    public function test_is_single_month_period_false_for_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = StatisticsReportQueryData::create();
        $data->dateFrom = Carbon::parse('2026-06-01');
        $data->dateTo = Carbon::parse('2026-08-01');

        $this->assertFalse($data->isSingleMonthPeriod());
    }

    public function test_detail_grid_month_uses_date_to_for_multi_month_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = StatisticsReportQueryData::create(
            StatisticsReportDetailLevel::BY_MONTH,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-08-01'),
        );

        $this->assertSame('2026-08-01', $data->detailGridMonth()->toDateString());
    }

    public function test_create_preserves_period_when_passed_explicitly(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = StatisticsReportQueryData::create(
            StatisticsReportDetailLevel::BY_DAY,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-01'),
        );

        $this->assertSame('2026-07-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-07-01', $data->dateTo->toDateString());
        $this->assertTrue(
            $data->columns->contains(fn ($column) => $column->field === 'day_1')
        );
        $this->assertSame(ChannelReportGrouping::NONE, $data->grouping);
    }
}
