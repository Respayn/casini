<?php

namespace Src\Domain\Reports;

use DateTimeImmutable;

final class ReportPeriodBucket
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
    ) {}

    public function containsMonth(DateTimeImmutable $month): bool
    {
        $value = $month->format('Y-m');

        return $value >= $this->start->format('Y-m') && $value <= $this->end->format('Y-m');
    }

    public function containsMonthKey(string $yearMonth): bool
    {
        $value = substr($yearMonth, 0, 7);

        return $value >= $this->start->format('Y-m') && $value <= $this->end->format('Y-m');
    }
}
