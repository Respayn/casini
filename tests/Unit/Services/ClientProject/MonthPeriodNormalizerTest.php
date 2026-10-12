<?php

namespace Tests\Unit\Services\ClientProject;

use App\Services\ClientProject\MonthPeriodNormalizer;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthPeriodNormalizerTest extends TestCase
{
    private MonthPeriodNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-13 12:00:00'));
        $this->normalizer = new MonthPeriodNormalizer;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function test_clamps_future_month_to_current(): void
    {
        $from = $this->normalizer->clampMonth(Carbon::parse('2026-09-01'), false);
        $to = $this->normalizer->clampMonth(Carbon::parse('2026-12-15'), true);

        $this->assertSame('2026-08-01', $from?->toDateString());
        $this->assertSame('2026-08-31', $to?->toDateString());
    }

    #[Test]
    public function test_keeps_past_and_current_month(): void
    {
        $from = $this->normalizer->clampMonth(Carbon::parse('2026-05-20'), false);
        $to = $this->normalizer->clampMonth(Carbon::parse('2026-08-01'), true);

        $this->assertSame('2026-05-01', $from?->toDateString());
        $this->assertSame('2026-08-31', $to?->toDateString());
    }

    #[Test]
    public function test_treats_epoch_as_empty(): void
    {
        $epoch = Carbon::parse('1970-01-01');

        $this->assertNull($this->normalizer->clampMonth($epoch, false));
        $this->assertNull($this->normalizer->toMin($epoch));
        $this->assertSame('2026-08-01', $this->normalizer->fromMax($epoch));
    }

    #[Test]
    public function test_aligns_inverted_range_when_from_changes(): void
    {
        [$from, $to] = $this->normalizer->alignRange(
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
        [$from, $to] = $this->normalizer->alignRange(
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
        $this->assertSame('2026-05-01', $this->normalizer->fromMax(Carbon::parse('2026-05-31')));
        $this->assertSame('2026-08-01', $this->normalizer->fromMax(Carbon::parse('2026-08-31')));
        $this->assertSame('2026-08-01', $this->normalizer->fromMax(null));
    }

    #[Test]
    public function test_to_min_follows_from(): void
    {
        $this->assertSame('2026-08-01', $this->normalizer->toMin(Carbon::parse('2026-08-15')));
        $this->assertNull($this->normalizer->toMin(null));
    }
}
