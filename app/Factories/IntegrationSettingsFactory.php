<?php

namespace App\Factories;

use App\Data\IntegrationSettings\CallibriIntegrationSettingsData;
use App\Data\IntegrationSettings\GoogleSheetsIntegrationSettingsData;
use App\Data\IntegrationSettings\IntegrationSettingsData;
use App\Data\IntegrationSettings\MegaplanIntegrationSettingsData;
use App\Data\IntegrationSettings\OneCActsIntegrationSettingsData;
use App\Data\IntegrationSettings\OneCAdBudgetFlowIntegrationSettingsData;
use App\Data\IntegrationSettings\OneCCheckIntegrationSettingsData;
use App\Data\IntegrationSettings\YandexDirectIntegrationSettingsData;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Data\IntegrationSettings\YandexSearchApiIntegrationSettingsData;

class IntegrationSettingsFactory
{
    public static function create(string $integrationCode): IntegrationSettingsData
    {
        return match ($integrationCode) {
            '1c_acts' => new OneCActsIntegrationSettingsData(),
            '1c_ad_budget_flow' => new OneCAdBudgetFlowIntegrationSettingsData(),
            '1c_check' => new OneCCheckIntegrationSettingsData(),
            'callibri' => new CallibriIntegrationSettingsData(),
            'google_sheets' => new GoogleSheetsIntegrationSettingsData(),
            'megaplan' => new MegaplanIntegrationSettingsData(),
            'yandex_direct' => new YandexDirectIntegrationSettingsData(),
            'yandex_metrika' => new YandexMetrikaIntegrationSettingsData(),
            'yandex_search_api' => new YandexSearchApiIntegrationSettingsData(),
            default => throw new \Exception("Integration $integrationCode not found")
        };
    }
}
