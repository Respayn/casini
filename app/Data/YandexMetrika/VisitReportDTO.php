<?php

namespace App\Data\YandexMetrika;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class VisitReportDTO extends Data
{
    public function __construct(
        public Carbon $startDate,
        public Carbon $endDate,
        public int $visits,
        public int $users,
        public array $queryParams,
        public array $totals,
        public array $minValues,
        public array $maxValues
    ) {
    }
}
