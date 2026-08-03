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
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use App\Models\Project;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class IntegrationSyncDispatcherTest extends TestCase
{
    use DatabaseTransactions;

    public function test_is_dispatch_window_only_at_00_01_local(): void
    {
        $dispatcher = new IntegrationSyncDispatcher([]);

        $this->assertTrue($dispatcher->isDispatchWindow(Carbon::parse('2026-08-03 00:01:00', 'Asia/Yekaterinburg')));
        $this->assertFalse($dispatcher->isDispatchWindow(Carbon::parse('2026-08-03 00:02:00', 'Asia/Yekaterinburg')));
        $this->assertFalse($dispatcher->isDispatchWindow(Carbon::parse('2026-08-03 12:01:00', 'Asia/Yekaterinburg')));
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

        $stub = new class implements IntegrationSyncCollector
        {
            public function key(): string
            {
                return 'stub';
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                return IntegrationSyncResult::success();
            }
        };

        $dispatcher = new IntegrationSyncDispatcher([$stub]);

        // 2026-08-03 00:01 Asia/Yekaterinburg = 2026-08-02 19:01 UTC
        $nowUtc = Carbon::parse('2026-08-02 19:01:00', 'UTC');

        $run1 = $dispatcher->dispatchIfDue($nowUtc);
        $run2 = $dispatcher->dispatchIfDue($nowUtc);

        $this->assertNotNull($run1);
        $this->assertNull($run2);
        $this->assertSame('2026-08-03', $run1->local_date->toDateString());
        $this->assertSame('2026-08-02', $run1->target_date->toDateString());
        $this->assertSame('Asia/Yekaterinburg', $run1->timezone);
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

        $dispatcher = new IntegrationSyncDispatcher([]);
        $ids = $dispatcher->candidateProjectIds();

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_start_run_dispatches_jobs_for_candidates(): void
    {
        Bus::fake();

        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();
        $project = Project::factory()->create(['is_active' => true]);

        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [
                'oauth_token' => 'token',
                'client_login' => 'client',
            ],
        ]);

        $stub = new class implements IntegrationSyncCollector
        {
            public function key(): string
            {
                return 'yandex_direct_daily_spend';
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                return IntegrationSyncResult::success();
            }
        };

        $dispatcher = new IntegrationSyncDispatcher([$stub]);
        $run = $dispatcher->startRun('2026-08-03', 'Asia/Yekaterinburg', '2026-08-02');

        $this->assertSame(IntegrationSyncRunStatus::Running, $run->status);
        $this->assertDatabaseHas('integration_sync_items', [
            'run_id' => $run->id,
            'project_id' => $project->id,
            'collector' => 'yandex_direct_daily_spend',
            'status' => IntegrationSyncItemStatus::Pending->value,
        ]);

        Bus::assertDispatched(ProcessIntegrationSyncItem::class);
    }
}
