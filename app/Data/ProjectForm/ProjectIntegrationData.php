<?php

namespace App\Data\ProjectForm;

use App\Data\Integrations\IntegrationData;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ProjectIntegrationData extends Data implements Wireable
{
    use WireableData;

    public IntegrationData $integration;
    public bool $isEnabled;
    public array $settings;
}
