<?php

namespace App\Factories;

use App\Data\IntegrationData;
use App\Data\IntegrationSettingsData;
use App\Data\MegaplanIntegrationSettingsData;
use Illuminate\Support\Collection;

class IntegrationSettingsFactory
{
    public static function create(string $integrationCode): IntegrationSettingsData
    {
        return match ($integrationCode) {
            'megaplan' => new MegaplanIntegrationSettingsData(),
            default => throw new \Exception('Integration not found')
        };
    }

    public static function createFromSettings(string $integrationCode, Collection $settings): IntegrationSettingsData
    {
        return match ($integrationCode) {
            'megaplan' => MegaplanIntegrationSettingsData::fromSettings($settings),
            default => throw new \Exception('Integration not found')
        };
    }
}
