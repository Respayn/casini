<?php

namespace App\Data\IntegrationSettings;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class Bitrix24IntegrationSettingsData extends IntegrationSettingsData
{
    public string $rootTask = '';

    public string $searchQuery = '';

    public bool $parseCommentWorks = false;

    public ?string $syncEnabledAt = null;
}
