<?php

namespace App\Data\Select;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class SelectOptionData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public string $value,
        public string $label
    ) {}
}
