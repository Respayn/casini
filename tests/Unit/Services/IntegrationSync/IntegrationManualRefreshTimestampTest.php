<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IntegrationManualRefreshTimestampTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-08-07 12:34:00', 'UTC'));
        config(['app.timezone' => 'UTC']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_returns_null_when_never_recorded(): void
    {
        $service = new IntegrationManualRefreshTimestamp();

        $this->assertNull($service->formattedLabel(1, 'channels'));
    }

    public function test_formats_recorded_timestamp(): void
    {
        $service = new IntegrationManualRefreshTimestamp();
        $service->record(7, 'channels');

        $label = $service->formattedLabel(7, 'channels');

        $this->assertNotNull($label);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}, \d{2}\.\d{2}\.\d{2}$/', $label);
    }

    public function test_product_keys_are_independent(): void
    {
        $service = new IntegrationManualRefreshTimestamp();
        $service->record(3, 'statistics');

        $this->assertNotNull($service->formattedLabel(3, 'statistics'));
        $this->assertNull($service->formattedLabel(3, 'channels'));
    }
}
