<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DateTimeHelper;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DateTimeHelperTest extends TestCase
{
    /**
     * Проверка интервалов для февраля 2024 (29 дней, високосный год)
     */
    public function test_get_month_week_intervals_feb_2024()
    {
        $date = Carbon::create(2024, 2, 15);
        $intervals = DateTimeHelper::getMonthWeekIntervals($date);

        $this->assertCount(5, $intervals);

        $this->assertEquals('2024-02-01', $intervals[0]['start']->format('Y-m-d'));
        $this->assertEquals('2024-02-04', $intervals[0]['end']->format('Y-m-d'));

        $this->assertEquals('2024-02-26', $intervals[4]['start']->format('Y-m-d'));
        $this->assertEquals('2024-02-29', $intervals[4]['end']->format('Y-m-d'));
    }

    public function test_get_month_week_intervals_nov_2025()
    {
        $date = Carbon::create(2025, 11, 10);
        $intervals = DateTimeHelper::getMonthWeekIntervals($date);

        $this->assertCount(5, $intervals);

        $this->assertEquals('2025-11-01', $intervals[0]['start']->format('Y-m-d'));
        $this->assertEquals('2025-11-02', $intervals[0]['end']->format('Y-m-d'));

        $this->assertEquals('2025-11-24', $intervals[4]['start']->format('Y-m-d'));
        $this->assertEquals('2025-11-30', $intervals[4]['end']->format('Y-m-d'));
    }
}
