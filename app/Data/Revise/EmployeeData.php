<?php

namespace App\Data\Revise;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class EmployeeData extends Data implements Wireable
{
    use WireableData;

    public int $id;

    public string $periodFrom;
    public string $periodTo;
    public string $name;

    /** @var ClientData[] $clients */
    public array $clients = [];

    public float $income = 0;
    public float $credit = 0;
    public float $cabinetReplenishment = 0;
    public float $workActsSum = 0;

    public string|float $outcome = '-';
}
