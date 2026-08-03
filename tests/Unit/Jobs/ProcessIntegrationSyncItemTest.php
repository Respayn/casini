<?php

namespace Tests\Unit\Jobs;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Enums\IntegrationSyncItemStatus;
use App\Enums\IntegrationSyncRunStatus;
use App\Jobs\ProcessIntegrationSyncItem;
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use App\Models\Project;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessIntegrationSyncItemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_requeues_item_to_end_on_retriable_failure(): void
    {
        Bus::fake([ProcessIntegrationSyncItem::class]);

        $project = Project::factory()->create(['is_active' => true]);

        $failing = new class implements IntegrationSyncCollector
        {
            public function key(): string
            {
                return 'failing';
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                return IntegrationSyncResult::failure('API down', requeue: true);
            }
        };

        $this->app->instance(
            IntegrationSyncDispatcher::class,
            new IntegrationSyncDispatcher([$failing])
        );

        $run = IntegrationSyncRun::query()->create([
            'local_date' => '2026-08-03',
            'timezone' => 'UTC',
            'target_date' => '2026-08-02',
            'status' => IntegrationSyncRunStatus::Running,
            'started_at' => now(),
        ]);

        $item = IntegrationSyncItem::query()->create([
            'run_id' => $run->id,
            'project_id' => $project->id,
            'collector' => 'failing',
            'status' => IntegrationSyncItemStatus::Pending,
            'attempts' => 0,
            'position' => 0,
        ]);

        IntegrationSyncItem::query()->create([
            'run_id' => $run->id,
            'project_id' => $project->id,
            'collector' => 'other',
            'status' => IntegrationSyncItemStatus::Pending,
            'attempts' => 0,
            'position' => 1,
        ]);

        (new ProcessIntegrationSyncItem($item->id))->handle(app(IntegrationSyncDispatcher::class));

        $item->refresh();
        $this->assertSame(IntegrationSyncItemStatus::Pending, $item->status);
        $this->assertSame(1, $item->attempts);
        $this->assertSame(2, $item->position);
        $this->assertSame('API down', $item->last_error);

        Bus::assertDispatched(ProcessIntegrationSyncItem::class);
    }

    public function test_marks_failed_after_max_attempts(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        $failing = new class implements IntegrationSyncCollector
        {
            public function key(): string
            {
                return 'failing';
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                return IntegrationSyncResult::failure('API down', requeue: true);
            }
        };

        $this->app->instance(
            IntegrationSyncDispatcher::class,
            new IntegrationSyncDispatcher([$failing])
        );

        $run = IntegrationSyncRun::query()->create([
            'local_date' => '2026-08-03',
            'timezone' => 'UTC',
            'target_date' => '2026-08-02',
            'status' => IntegrationSyncRunStatus::Running,
            'started_at' => now(),
        ]);

        $item = IntegrationSyncItem::query()->create([
            'run_id' => $run->id,
            'project_id' => $project->id,
            'collector' => 'failing',
            'status' => IntegrationSyncItemStatus::Pending,
            'attempts' => IntegrationSyncDispatcher::MAX_ATTEMPTS - 1,
            'position' => 0,
        ]);

        (new ProcessIntegrationSyncItem($item->id))->handle(app(IntegrationSyncDispatcher::class));

        $item->refresh();
        $this->assertSame(IntegrationSyncItemStatus::Failed, $item->status);
        $this->assertSame(IntegrationSyncDispatcher::MAX_ATTEMPTS, $item->attempts);

        $run->refresh();
        $this->assertSame(IntegrationSyncRunStatus::Failed, $run->status);
    }
}
