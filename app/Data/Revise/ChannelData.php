<?php

namespace App\Data\Revise;

use Spatie\LaravelData\Data;

class ChannelData extends Data
{
    public ?int $id;
    public string $name;

    /** @var ReviseData[] $revises */
    public array $revises = [];

    public float $income = 0;
    public float $credit = 0;
    public string|float $outcome = '-';
    public float $cabinetReplenishment = 0;
    public float $workActsSum = 0;
}
