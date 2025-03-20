<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BonusIntervalData extends Data
{
    public function __construct(
        public float $fromPercentage,
        public float $toPercentage,
        public ?float $bonusAmount = null,
        public ?float $bonusPercentage = null,
    ) {
    }
}
