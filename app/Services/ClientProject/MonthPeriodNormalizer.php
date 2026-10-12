<?php

namespace App\Services\ClientProject;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class MonthPeriodNormalizer
{
    public function clampMonth(?CarbonInterface $date, bool $toEndOfMonth): ?Carbon
    {
        if ($this->isEmpty($date)) {
            return null;
        }

        $month = $date->copy()->startOfMonth();
        $current = Carbon::now()->startOfMonth();

        if ($month->gt($current)) {
            $month = $current->copy();
        }

        return $toEndOfMonth
            ? $month->copy()->endOfMonth()->startOfDay()
            : $month;
    }

    /**
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    public function alignRange(?CarbonInterface $from, ?CarbonInterface $to, string $changed): array
    {
        if ($this->isEmpty($from) || $this->isEmpty($to)) {
            return [$from, $to];
        }

        if ($from->lte($to)) {
            return [$from, $to];
        }

        if ($changed === 'from') {
            $to = $from->copy()->endOfMonth()->startOfDay();
        } else {
            $from = $to->copy()->startOfMonth();
        }

        return [$from, $to];
    }

    public function fromMax(?CarbonInterface $to): string
    {
        $max = Carbon::now()->startOfMonth();

        if (! $this->isEmpty($to) && $to->copy()->startOfMonth()->lt($max)) {
            $max = $to->copy()->startOfMonth();
        }

        return $max->toDateString();
    }

    public function toMin(?CarbonInterface $from): ?string
    {
        if ($this->isEmpty($from)) {
            return null;
        }

        return $from->copy()->startOfMonth()->toDateString();
    }

    private function isEmpty(?CarbonInterface $date): bool
    {
        if ($date === null) {
            return true;
        }

        return $date->year === 1970
            && $date->month === 1
            && $date->day === 1;
    }
}
