<?php

namespace Tests\Unit\Support;

use App\Support\IntegrationLogo;
use Tests\TestCase;

class IntegrationLogoTest extends TestCase
{
    public function test_channels_and_statistics_share_google_sheets_icon(): void
    {
        $this->assertSame('google-sheets', IntegrationLogo::component('google_sheets'));
        $this->assertSame('yandex-direct', IntegrationLogo::component('yandex_direct'));
        $this->assertSame('bitrix24', IntegrationLogo::component('bitrix24'));
        $this->assertSame('1c', IntegrationLogo::component('1c_acts'));
        $this->assertSame('1c', IntegrationLogo::component('1c_check'));
        $this->assertSame('1c', IntegrationLogo::component('1c_ad_budget_flow'));
    }
}
