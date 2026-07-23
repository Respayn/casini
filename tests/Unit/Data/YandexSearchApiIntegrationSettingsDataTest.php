<?php

namespace Tests\Unit\Services;

use App\Factories\IntegrationSettingsFactory;
use App\Data\IntegrationSettings\YandexSearchApiIntegrationSettingsData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexSearchApiIntegrationSettingsDataTest extends TestCase
{
    #[Test]
    public function test_factory_returns_default_settings(): void
    {
        $settings = IntegrationSettingsFactory::create('yandex_search_api');

        $this->assertInstanceOf(YandexSearchApiIntegrationSettingsData::class, $settings);
        $this->assertSame([], $settings->regions);
        $this->assertNull($settings->syncEnabledAt);
    }
}
