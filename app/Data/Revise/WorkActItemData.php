<?php

namespace App\Data\Revise;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class WorkActItemData extends Data implements Wireable
{
    use WireableData;

    public $number;
    public $name;
    public $quantity;
    public $unit;
    public $price;
    public $total;
}
