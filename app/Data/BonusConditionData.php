<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class BonusConditionData extends Data
{

    public function __construct(
        public bool $bonuses_enabled = false,
        public bool $calculate_in_percentage = false,
        public ?float $client_payment = null,
        public int $start_month = 1,
        #[DataCollectionOf(BonusIntervalData::class)]
        public ?DataCollection $intervals = null,
    ) {
    }
}
