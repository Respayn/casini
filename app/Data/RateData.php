<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class RateData extends Data implements Wireable
{
	use WireableData;

	public int $id;
	public string $name;
	public ?CarbonImmutable $createdAt;
	public ?CarbonImmutable $updatedAt;
	public int $actualValue;
	public ?CarbonImmutable $actualStartDate;
	public ?CarbonImmutable $actualEndDate;

	/** @var Collection<int, RateValueData> */
	public Collection $values;
}
