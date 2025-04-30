<?php

namespace App\Data\Callibri;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class SiteStatisticData extends Data
{
    public function __construct(
        public Carbon $date,
        public int $visits,
        public int $calls,
    ) {
    }
}
