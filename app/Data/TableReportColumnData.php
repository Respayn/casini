<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class TableReportColumnData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public string $field,
        public string $label,
        public bool $isVisible,
        public int $order
    ) {}
}
