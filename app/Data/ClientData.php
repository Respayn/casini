<?php

namespace App\Data;

use App\Models\Client;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
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
        public float $initialBalance,
        public int $managerId,
        public ?UserData $manager,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        #[DataCollectionOf(ProjectData::class)]
        public ?DataCollection $projects = null
    ) {}
}
