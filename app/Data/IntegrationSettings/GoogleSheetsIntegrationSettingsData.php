<?php

namespace App\Data\IntegrationSettings;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class GoogleSheetsIntegrationSettingsData extends IntegrationSettingsData
{
    public string $documentId = '';

    public ?string $syncEnabledAt = null;

    public ?string $oauthToken = null;

    public ?string $refreshToken = null;
}
