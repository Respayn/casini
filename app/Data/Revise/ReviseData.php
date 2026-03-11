<?php

namespace App\Data\Revise;

use Carbon\Carbon;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ReviseData extends Data implements Wireable
{
    use WireableData;

    public Carbon $date;
    public float $income = 0;
    public float|string $outcome = '-';
    public float $credit = 0;
    public int $creditCount = 0;
    public int $incomeCount = 0;

    public float $cabinetReplenishment = 0;
    public int $cabinetReplenishmentCount = 0;
    public float $workActsSum = 0;
    public array $workActs = [];
}
