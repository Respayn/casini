<?php

namespace App\Data\YandexDirect;

class PerformanceReportDTO
{
    public function __construct(
        public readonly string $date,
        public readonly int $impressions,
        public readonly int $clicks,
        public readonly float $cost,
        public readonly float $ctr
    ) {
    }
}
