<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Enums\IntegrationSyncItemStatus;
use App\Enums\IntegrationSyncRunStatus;
use App\Jobs\ProcessIntegrationSyncItem;
use App\Models\Agency;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class IntegrationSyncDispatcherTest extends TestCase
{
    use DatabaseTransactions;

    public function test_is_dispatch_window_from_00_01_local(): void
    {
        $dispatcher = new IntegrationSyncDispatcher([]);

        $this->assertFalse($dispatcher->isDispatchWindow(Carbon::parse('2026-08-03 00:00:00', 'Asia/Yekaterinburg')));
        $this->assertTrue($dispatcher->isDispatchWindow(Carbon::parse('2026-08-03 00:01:00', 'Asia/Yekaterinburg')));
        $this->assertTrue($dispatcher->isDispatchWindow(Carbon::parse('2026-08-03 00:02:00', 'Asia/Yekaterinburg')));
        $this->assertTrue($dispatcher->isDispatchWindow(Carbon::parse('2026-08-03 12:01:00', 'Asia/Yekaterinburg')));
    }

    public function test_dispatch_if_due_starts_run_once_for_local_date(): void
    {
        Bus::fake();

        $agency = Agency::query()->orderBy('id')->first();
        if ($agency === null) {
            Agency::factory()->create(['time_zone' => 'Asia/Yekaterinburg']);
        } else {
            $agency->update(['time_zone' => 'Asia/Yekaterinburg']);
        }

        $dispatcher = new IntegrationSyncDispatcher([$this->makeStubCollector('stub', true)]);

        $nowUtc = Carbon::parse('2026-08-02 19:01:00', 'UTC');

        $run1 = $dispatcher->dispatchIfDue($nowUtc);
        $run2 = $dispatcher->dispatchIfDue($nowUtc);

        $this->assertNotNull($run1);
        $this->assertNull($run2);
        $this->assertSame('2026-08-03', $run1->local_date->toDateString());
        $this->assertSame('2026-08-02', $run1->target_date->toDateString());
        $this->assertSame('Asia/Yekaterinburg', $run1->timezone);
    }

    public function test_dispatch_if_due_catches_up_after_00_01_if_run_missing(): void
    {
        Bus::fake();

        $agency = Agency::query()->orderBy('id')->first();
        if ($agency === null) {
            Agency::factory()->create(['time_zone' => 'Asia/Yekaterinburg']);
        } else {
            $agency->update(['time_zone' => 'Asia/Yekaterinburg']);
        }

        $dispatcher = new IntegrationSyncDispatcher([$this->makeStubCollector('stub', true)]);

        $afterWindow = Carbon::parse('2026-08-02 19:15:00', 'UTC');

        $run = $dispatcher->dispatchIfDue($afterWindow);

        $this->assertNotNull($run);
        $this->assertSame('2026-08-03', $run->local_date->toDateString());
        $this->assertSame('2026-08-02', $run->target_date->toDateString());
        $this->assertNull($dispatcher->dispatchIfDue($afterWindow));
    }

    public function test_start_run_skips_collector_when_supports_project_throws(): void
    {
        Bus::fake();

        $project = Project::factory()->create(['is_active' => true]);

        $broken = $this->makeStubCollector('broken', function () {
            throw new \RuntimeException('missing callibri()');
        });
        $searchApi = $this->makeStubCollector('yandex_search_api_daily_positions', true);

        $dispatcher = new IntegrationSyncDispatcher([$broken, $searchApi]);
        $run = $dispatcher->startRun('2026-08-13', 'Asia/Yekaterinburg', '2026-08-12');

        $this->assertDatabaseMissing('integration_sync_items', [
            'run_id' => $run->id,
            'collector' => 'broken',
        ]);
        $this->assertDatabaseHas('integration_sync_items', [
            'run_id' => $run->id,
            'project_id' => $project->id,
            'collector' => 'yandex_search_api_daily_positions',
            'status' => IntegrationSyncItemStatus::Pending->value,
        ]);
    }

    public function test_candidate_project_ids_skips_inactive_projects(): void
    {
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        $active = Project::factory()->create(['is_active' => true]);
        $inactive = Project::factory()->create(['is_active' => false]);

        IntegrationProject::query()->create([
            'project_id' => $active->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [
                'oauth_token' => 't1',
                'client_login' => 'login1',
            ],
        ]);
        IntegrationProject::query()->create([
            'project_id' => $inactive->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [
                'oauth_token' => 't2',
                'client_login' => 'login2',
            ],
        ]);

        $dispatcher = new IntegrationSyncDispatcher([
            app(\App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector::class),
        ]);
        $ids = $dispatcher->candidateProjectIds();

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_start_run_dispatches_jobs_only_for_supported_projects(): void
    {
        Bus::fake();

        $projectSupported = Project::factory()->create(['is_active' => true]);
        $projectUnsupported = Project::factory()->create(['is_active' => true]);

        $stub = $this->makeStubCollector(
            'yandex_direct_daily_spend',
            fn (int $projectId) => $projectId === $projectSupported->id,
        );

        $dispatcher = new IntegrationSyncDispatcher([$stub]);
        $run = $dispatcher->startRun('2026-08-03', 'Asia/Yekaterinburg', '2026-08-02');

        $this->assertSame(IntegrationSyncRunStatus::Running, $run->status);
        $this->assertDatabaseHas('integration_sync_items', [
            'run_id' => $run->id,
            'project_id' => $projectSupported->id,
            'collector' => 'yandex_direct_daily_spend',
            'status' => IntegrationSyncItemStatus::Pending->value,
        ]);
        $this->assertDatabaseMissing('integration_sync_items', [
            'run_id' => $run->id,
            'project_id' => $projectUnsupported->id,
        ]);

        Bus::assertDispatched(ProcessIntegrationSyncItem::class);
    }

    /**
     * @param  bool|callable(int): bool  $supports
     */
    private function makeStubCollector(string $key, bool|callable $supports): IntegrationSyncCollector
    {
        return new class($key, $supports) implements IntegrationSyncCollector
        {
            public function __construct(
                private readonly string $collectorKey,
                private readonly mixed $supports,
            ) {}

            public function key(): string
            {
                return $this->collectorKey;
            }

            public function integrationCode(): string
            {
                return 'stub';
            }

            public function supportsProject(int $projectId): bool
            {
                return is_callable($this->supports)
                    ? (bool) ($this->supports)($projectId)
                    : (bool) $this->supports;
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                return IntegrationSyncResult::success();
            }

            public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
            {
                return IntegrationSyncResult::success();
            }
        };
    }
}
