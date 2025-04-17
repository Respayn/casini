<?php

namespace App\Factories;

use App\Data\Integrations\IntegrationSettingsData;
use App\Data\Integrations\MegaplanIntegrationSettingsData;
use App\Data\Integrations\YandexDirectIntegrationSettingsData;
use Illuminate\Support\Collection;

class IntegrationSettingsFactory
{
    public static function create(string $integrationCode): IntegrationSettingsData
    {
        return match ($integrationCode) {
            'megaplan' => new MegaplanIntegrationSettingsData(),
            'yandex_direct' => new YandexDirectIntegrationSettingsData(),
            default => throw new \Exception('Integration not found')
        };
    }

    public static function createFromSettings(string $integrationCode, Collection $settings): IntegrationSettingsData
    {
        return match ($integrationCode) {
            'megaplan' => MegaplanIntegrationSettingsData::fromSettings($settings),
            'yandex_direct' => YandexDirectIntegrationSettingsData::fromSettings($settings),
            default => throw new \Exception('Integration not found')
        };
    }
}
