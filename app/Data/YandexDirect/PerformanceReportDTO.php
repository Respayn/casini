<?php

namespace App\Data\YandexDirect;

class PerformanceReportDTO
{
    public function __construct(
        public int $impressions,
        public int $clicks,
        public float $cost
    ) {}
}
