<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Services\IntegrationSync\YandexMetrikaSyncPeriod;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class YandexMetrikaSyncPeriodTest extends TestCase
{
    public function test_nightly_requests_whole_month_up_to_yesterday(): void
    {
        $period = YandexMetrikaSyncPeriod::nightly(Carbon::parse('2026-08-17'), '2026-08-01');

        $this->assertSame(['2026-08-01', '2026-08-17'], $this->dates($period));
    }

    public function test_nightly_does_not_reload_previous_months(): void
    {
        $period = YandexMetrikaSyncPeriod::nightly(Carbon::parse('2026-08-17'), '2026-06-10');

        $this->assertSame(['2026-08-01', '2026-08-17'], $this->dates($period));
    }

    public function test_nightly_skips_when_sync_enabled_after_yesterday(): void
    {
        $this->assertNull(YandexMetrikaSyncPeriod::nightly(Carbon::parse('2026-08-17'), '2026-08-18'));
    }

    public function test_manual_range_keeps_several_months(): void
    {
        $period = YandexMetrikaSyncPeriod::range(
            Carbon::parse('2026-06-15'),
            Carbon::parse('2026-08-17'),
            '2026-05-01',
        );

        $this->assertSame(['2026-06-01', '2026-08-17'], $this->dates($period));
    }

    public function test_manual_range_starts_from_sync_enabled_month(): void
    {
        $period = YandexMetrikaSyncPeriod::range(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-08-17'),
            '2026-07-20',
        );

        $this->assertSame(['2026-07-01', '2026-08-17'], $this->dates($period));
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $period
     * @return array{0: string, 1: string}
     */
    private function dates(?array $period): array
    {
        $this->assertNotNull($period);

        return [$period[0]->toDateString(), $period[1]->toDateString()];
    }
}
