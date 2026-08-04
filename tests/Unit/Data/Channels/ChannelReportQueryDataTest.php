<?php

namespace Tests\Unit\Data\Channels;

use App\Data\Channels\ChannelReportQueryData;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChannelReportQueryDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_create_defaults_both_ends_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 15:00:00'));

        $data = ChannelReportQueryData::create();

        $this->assertSame('2026-08-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-08-01', $data->dateTo->toDateString());
        $this->assertTrue($data->isSingleMonthPeriod());
    }

    public function test_from_saved_settings_fills_missing_date_from(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $payload = ChannelReportQueryData::create()->toArray();
        unset($payload['dateFrom']);
        $payload['dateTo'] = '2026-07-01T00:00:00+00:00';

        $data = ChannelReportQueryData::hydrateFromSavedSettings($payload);

        $this->assertSame('2026-07-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-07-01', $data->dateTo->toDateString());
    }

    public function test_clamp_period_blocks_future_and_swaps_inverted_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = ChannelReportQueryData::create();
        $data->dateFrom = Carbon::parse('2026-10-01');
        $data->dateTo = Carbon::parse('2026-09-01');
        $data->clampPeriodToPresent();

        $this->assertSame('2026-08-01', $data->dateFrom->toDateString());
        $this->assertSame('2026-08-01', $data->dateTo->toDateString());
    }

    public function test_is_single_month_period_false_for_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04'));

        $data = ChannelReportQueryData::create();
        $data->dateFrom = Carbon::parse('2026-06-01');
        $data->dateTo = Carbon::parse('2026-08-01');

        $this->assertFalse($data->isSingleMonthPeriod());
    }
}
