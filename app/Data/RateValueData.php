<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class RateValueData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public int $rateId;
    public int $value;
    public ?CarbonImmutable $startDate;
    public ?CarbonImmutable $endDate;
    public ?CarbonImmutable $createdAt;
    public ?CarbonImmutable $updatedAt;
}
