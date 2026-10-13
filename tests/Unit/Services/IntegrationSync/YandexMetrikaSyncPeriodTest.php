<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Services\IntegrationSync\YandexMetrikaSyncPeriod;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\DateTimeRange;

class YandexMetrikaSyncPeriodTest extends TestCase
{
    private YandexMetrikaSyncPeriod $syncPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncPeriod = new YandexMetrikaSyncPeriod;
    }

    public function test_nightly_requests_whole_month_up_to_yesterday(): void
    {
        $period = $this->syncPeriod->nightly(Carbon::parse('2026-08-17'), '2026-08-01');

        $this->assertSame(['2026-08-01', '2026-08-17'], $this->dates($period));
    }

    public function test_nightly_does_not_reload_previous_months(): void
    {
        $period = $this->syncPeriod->nightly(Carbon::parse('2026-08-17'), '2026-06-10');

        $this->assertSame(['2026-08-01', '2026-08-17'], $this->dates($period));
    }

    public function test_nightly_skips_when_sync_enabled_after_yesterday(): void
    {
        $this->assertNull($this->syncPeriod->nightly(Carbon::parse('2026-08-17'), '2026-08-18'));
    }

    public function test_manual_range_keeps_several_months(): void
    {
        $period = $this->syncPeriod->range(
            Carbon::parse('2026-06-15'),
            Carbon::parse('2026-08-17'),
            '2026-05-01',
        );

        $this->assertSame(['2026-06-01', '2026-08-17'], $this->dates($period));
    }

    public function test_manual_range_starts_from_sync_enabled_month(): void
    {
        $period = $this->syncPeriod->range(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-08-17'),
            '2026-07-20',
        );

        $this->assertSame(['2026-07-01', '2026-08-17'], $this->dates($period));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dates(?DateTimeRange $period): array
    {
        $this->assertNotNull($period);

        return [$period->getStart()->format('Y-m-d'), $period->getEnd()->format('Y-m-d')];
    }
}
