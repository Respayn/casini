<?php

namespace App\Data\IntegrationSettings;

use Livewire\Wireable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class YandexSearchApiIntegrationSettingsData extends IntegrationSettingsData implements Wireable
{
    use WireableData;

    public ?string $syncEnabledAt = null;

    /** @var array<int, array{code: int|null, phrases: string[]}> */
    public array $regions = [];
}
