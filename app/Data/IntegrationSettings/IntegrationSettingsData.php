<?php

namespace App\Data\IntegrationSettings;

use App\Data\Integrations\IntegrationData;
use Carbon\CarbonImmutable;
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
