<?php

namespace App\Data\Revise;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ClientData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public string $name;
    public float $initialBalance;

    public string|float $outcome = '-';

    /** @var ChannelData[] $channels */
    public array $channels = [];
}
