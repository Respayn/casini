<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Livewire\Wireable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ClientData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?int $id,
        public string $name,
        public string $inn,
        public float $initial_balance,
        public int $manager_id,
        public ?UserData $manager = null,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $updatedAt = null,
        #[DataCollectionOf(ProjectData::class)]
        public ?DataCollection $projects = null
    ) {}
}
