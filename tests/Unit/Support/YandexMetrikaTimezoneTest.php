<?php

namespace Tests\Unit\Support;

use App\Support\YandexMetrikaTimezone;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Query;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaTimezoneTest extends TestCase
{
    #[Test]
    public function test_yekaterinburg_offset(): void
    {
        $at = Carbon::parse('2026-08-18 12:00:00', 'UTC');

        $this->assertSame('+05:00', YandexMetrikaTimezone::offsetFor('Asia/Yekaterinburg', $at));
    }

    #[Test]
    public function test_moscow_offset(): void
    {
        $at = Carbon::parse('2026-08-18 12:00:00', 'UTC');

        $this->assertSame('+03:00', YandexMetrikaTimezone::offsetFor('Europe/Moscow', $at));
    }

    #[Test]
    public function test_empty_and_invalid_timezone_return_null(): void
    {
        $this->assertNull(YandexMetrikaTimezone::offsetFor(null));
        $this->assertNull(YandexMetrikaTimezone::offsetFor(''));
        $this->assertNull(YandexMetrikaTimezone::offsetFor('   '));
        $this->assertNull(YandexMetrikaTimezone::offsetFor('Not/AZone'));
    }

    #[Test]
    public function test_offset_if_differs_returns_null_when_same_offset(): void
    {
        $at = Carbon::parse('2026-08-18 12:00:00', 'UTC');

        $this->assertNull(YandexMetrikaTimezone::offsetIfDiffers(
            'Asia/Yekaterinburg',
            'Asia/Yekaterinburg',
            $at
        ));
    }

    #[Test]
    public function test_offset_if_differs_returns_agency_offset_when_counter_is_moscow(): void
    {
        $at = Carbon::parse('2026-08-18 12:00:00', 'UTC');

        $this->assertSame('+05:00', YandexMetrikaTimezone::offsetIfDiffers(
            'Asia/Yekaterinburg',
            'Europe/Moscow',
            $at
        ));
    }

    #[Test]
    public function test_offset_if_differs_returns_null_when_counter_timezone_unknown(): void
    {
        $at = Carbon::parse('2026-08-18 12:00:00', 'UTC');

        $this->assertNull(YandexMetrikaTimezone::offsetIfDiffers('Asia/Yekaterinburg', null, $at));
        $this->assertNull(YandexMetrikaTimezone::offsetIfDiffers('Asia/Yekaterinburg', '', $at));
    }

    #[Test]
    public function test_plus_in_offset_is_percent_encoded_for_query(): void
    {
        $query = Query::build(['timezone' => '+05:00'], PHP_QUERY_RFC3986);

        $this->assertSame('timezone=%2B05%3A00', $query);
    }
}
