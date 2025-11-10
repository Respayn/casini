<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class DateTimeHelper
{
    /**
     * Create a new class instance.
     */
    public static function getMonthWeekIntervals(Carbon $date): array
    {
        $date = $date->copy()->startOfDay();
        $startOfMonth = $date->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $firstWeekStart = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $intervals = [];
        $current = $firstWeekStart;

        while ($current <= $lastWeekEnd) {
            $weekStart = $current->copy()->startOfDay();
            $weekEnd = $current->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

            $intervalStart = $weekStart->max($startOfMonth);
            $intervalEnd = $weekEnd->min($endOfMonth);

            $intervals[] = [
                'start' => $intervalStart,
                'end' => $intervalEnd
            ];

            $current->addWeek();
        }

        return $intervals;
    }

    public static function getMonthName(int $monthNum): string
    {
        $months = [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь'
        ];

        return isset($months[$monthNum]) ? $months[$monthNum] : '';
    }
}
