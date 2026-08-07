<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Services\IntegrationSync\IntegrationApiThrottle;
use App\Services\IntegrationSync\IntegrationMetricsRefreshService;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class IntegrationMetricsRefreshServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-08-07 12:00:00'));
        Auth::shouldReceive('id')->andReturn(1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public function test_refreshes_all_supported_collectors_for_project(): void
    {
        $calls = [];

        $direct = $this->makeCollector('yandex_direct_daily_spend', true, $calls);
        $callibri = $this->makeCollector('callibri_daily_leads', true, $calls);
        $unsupported = $this->makeCollector('other', false, $calls);

        $dispatcher = new IntegrationSyncDispatcher([$direct, $callibri, $unsupported]);
        $service = new IntegrationMetricsRefreshService($dispatcher, new IntegrationApiThrottle());

        $stats = $service->refreshDataForProjects(
            [10],
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-01'),
        );

        $this->assertSame(1, $stats['updated']);
        $this->assertSame(0, $stats['failed']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(['yandex_direct_daily_spend', 'callibri_daily_leads'], $calls);
    }

    public function test_skips_project_without_collectors(): void
    {
        $calls = [];
        $collector = $this->makeCollector('stub', false, $calls);
        $dispatcher = new IntegrationSyncDispatcher([$collector]);
        $service = new IntegrationMetricsRefreshService($dispatcher, new IntegrationApiThrottle());

        $stats = $service->refreshDataForProjects(
            [5],
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-01'),
        );

        $this->assertSame(0, $stats['updated']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame([], $calls);
    }

    /**
     * @param  list<string>  $calls
     */
    private function makeCollector(string $key, bool $supports, array &$calls): IntegrationSyncCollector
    {
        return new class($key, $supports, $calls) implements IntegrationSyncCollector
        {
            public function __construct(
                private readonly string $collectorKey,
                private readonly bool $supports,
                private array &$calls,
            ) {}

            public function key(): string
            {
                return $this->collectorKey;
            }

            public function integrationCode(): string
            {
                return $this->collectorKey;
            }

            public function supportsProject(int $projectId): bool
            {
                return $this->supports;
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                return IntegrationSyncResult::success();
            }

            public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
            {
                $this->calls[] = $this->collectorKey;

                return IntegrationSyncResult::success();
            }
        };
    }
}
