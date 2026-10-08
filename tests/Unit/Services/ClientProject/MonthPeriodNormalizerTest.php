<?php

namespace Tests\Unit\Services\ClientProject;

use App\Services\ClientProject\MonthPeriodNormalizer;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthPeriodNormalizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-13 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function test_clamps_future_month_to_current(): void
    {
        $from = MonthPeriodNormalizer::clampMonth(Carbon::parse('2026-09-01'), false);
        $to = MonthPeriodNormalizer::clampMonth(Carbon::parse('2026-12-15'), true);

        $this->assertSame('2026-08-01', $from?->toDateString());
        $this->assertSame('2026-08-31', $to?->toDateString());
    }

    #[Test]
    public function test_keeps_past_and_current_month(): void
    {
        $from = MonthPeriodNormalizer::clampMonth(Carbon::parse('2026-05-20'), false);
        $to = MonthPeriodNormalizer::clampMonth(Carbon::parse('2026-08-01'), true);

        $this->assertSame('2026-05-01', $from?->toDateString());
        $this->assertSame('2026-08-31', $to?->toDateString());
    }

    #[Test]
    public function test_treats_epoch_as_empty(): void
    {
        $this->assertTrue(MonthPeriodNormalizer::isEmpty(Carbon::parse('1970-01-01')));
        $this->assertNull(MonthPeriodNormalizer::clampMonth(Carbon::parse('1970-01-01'), false));
        $this->assertNull(MonthPeriodNormalizer::toMin(Carbon::parse('1970-01-01')));
    }

    #[Test]
    public function test_aligns_inverted_range_when_from_changes(): void
    {
        [$from, $to] = MonthPeriodNormalizer::alignRange(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-05-31')->startOfDay(),
            'from'
        );

        $this->assertSame('2026-08-01', $from?->toDateString());
        $this->assertSame('2026-08-31', $to?->toDateString());
    }

    #[Test]
    public function test_aligns_inverted_range_when_to_changes(): void
    {
        [$from, $to] = MonthPeriodNormalizer::alignRange(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-05-31')->startOfDay(),
            'to'
        );

        $this->assertSame('2026-05-01', $from?->toDateString());
        $this->assertSame('2026-05-31', $to?->toDateString());
    }

    #[Test]
    public function test_from_max_is_limited_by_to_and_current_month(): void
    {
        $this->assertSame('2026-05-01', MonthPeriodNormalizer::fromMax(Carbon::parse('2026-05-31')));
        $this->assertSame('2026-08-01', MonthPeriodNormalizer::fromMax(Carbon::parse('2026-08-31')));
        $this->assertSame('2026-08-01', MonthPeriodNormalizer::fromMax(null));
    }

    #[Test]
    public function test_to_min_follows_from(): void
    {
        $this->assertSame('2026-08-01', MonthPeriodNormalizer::toMin(Carbon::parse('2026-08-15')));
        $this->assertNull(MonthPeriodNormalizer::toMin(null));
    }
}
