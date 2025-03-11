<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BonusData extends Data
{
    public function __construct(
        public bool $bonusesEnabled,
        public bool $calculateInPercentage,
        public ?float $clientPayment,
        public int $startMonth,
        public array $intervals,
    ) {}
}
