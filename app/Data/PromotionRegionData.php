<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class PromotionRegionData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
