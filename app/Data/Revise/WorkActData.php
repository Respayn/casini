<?php

namespace App\Data\Revise;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class WorkActData extends Data implements Wireable
{
    use WireableData;

    public $number;
    public $date;
    public $price;

    public array $items = [];
}
