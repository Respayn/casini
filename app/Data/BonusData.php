<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BonusData extends Data
{
    /**
     * @var IntervalData[]
     */
    public array $intervals;

    public function __construct(
        public bool $bonuses_enabled,
        public bool $calculate_in_percentage,
        public ?float $client_payment,
        public int $start_month,
        array $intervals,
    ) {
        $this->intervals = $intervals;
    }
}
