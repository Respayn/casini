<?php

namespace App\Data\Accounting;

use Spatie\LaravelData\Data;

class WorkActItemData extends Data
{
    public function __construct(
        public int $number,
        public string $name,
        public float $count,
        public string $unit,
        public float $price,
        public float $total
    ) {}
}
