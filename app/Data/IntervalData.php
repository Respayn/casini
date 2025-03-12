<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class IntervalData extends Data
{
    public function __construct(
        public float $from_percentage,
        public float $to_percentage,
        public ?float $bonus_amount,
        public ?float $bonus_percentage,
    ) {}
}
