<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class IntegrationSettingsData extends Data implements Wireable
{
    use WireableData;

    public ?int $integrationId;
    public ?IntegrationData $integration;

    public bool $isEnabled;
    public ?CarbonImmutable $createdAt;
    public ?CarbonImmutable $updatedAt;
}
