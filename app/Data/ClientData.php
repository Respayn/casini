<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ClientData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public string $name;
    public string $inn;
    public float $initialBalance;
    public int $managerId;

    public ?UserData $manager;

    public CarbonImmutable $createdAt;

    public CarbonImmutable $updatedAt;
}
