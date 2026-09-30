<?php

namespace Tests\Unit\Console;

use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class BackfillDirectSpendCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_requires_options(): void
    {
        $this->artisan('integrations:backfill-direct-spend')
            ->assertFailed();
    }

    public function test_rejects_invalid_date_order(): void
    {
        $this->artisan('integrations:backfill-direct-spend', [
            '--project' => '1',
            '--from' => '2026-08-06',
            '--to' => '2026-08-04',
        ])->assertFailed();
    }

    public function test_calls_collector_for_range(): void
    {
        $collector = Mockery::mock(YandexDirectDailySpendCollector::class);
        $collector->shouldReceive('collectRange')
            ->once()
            ->withArgs(function (int $projectId, Carbon $from, Carbon $to) {
                return $projectId === 1
                    && $from->toDateString() === '2026-08-04'
                    && $to->toDateString() === '2026-08-06';
            })
            ->andReturn(IntegrationSyncResult::success());

        $this->app->instance(YandexDirectDailySpendCollector::class, $collector);

        $this->artisan('integrations:backfill-direct-spend', [
            '--project' => '1',
            '--from' => '2026-08-04',
            '--to' => '2026-08-06',
        ])->assertSuccessful();
    }
}
