<?php

namespace App\Data\IntegrationSettings;

use Livewire\Wireable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CallibriIntegrationSettingsData extends IntegrationSettingsData implements Wireable
{
    use WireableData;

    public string $email = '';

    public string $token = '';

    public ?int $siteId = null;

    public ?string $syncEnabledAt = null;

    public string $utmFilterMode = 'none';

    public string $utmSource = '';

    public string $utmMedium = '';

    public string $utmCampaign = '';

    /** @var string[] */
    public array $appealsType = [];

    public string $appealsFilter = 'all';

    public string $leadCostCalc = 'all';

    public string $appealsClass = '';
}
