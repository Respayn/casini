<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ProductData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public string $name;
    public bool $isRestricted;
    public ?string $notification;
    public string $code;
}
