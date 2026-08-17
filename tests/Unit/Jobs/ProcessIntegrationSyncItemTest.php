<?php

namespace Tests\Unit\Jobs;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Enums\IntegrationSyncItemStatus;
use App\Enums\IntegrationSyncRunStatus;
use App\Events\Notifications\IntegrationSyncFailed;
use App\Jobs\ProcessIntegrationSyncItem;
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use App\Models\Project;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class ProcessIntegrationSyncItemTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([IntegrationSyncFailed::class]);
    }

    public function test_requeues_item_to_end_on_retriable_failure(): void
    {
        Bus::fake([ProcessIntegrationSyncItem::class]);

        $project = Project::factory()->create(['is_active' => true]);

        $failing = $this->makeFailingCollector();

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
        Event::assertNotDispatched(IntegrationSyncFailed::class);
    }

    public function test_marks_failed_after_max_attempts(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        $failing = $this->makeFailingCollector();

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
        Event::assertDispatched(IntegrationSyncFailed::class, function (IntegrationSyncFailed $event) use ($project) {
            return $event->projectId === $project->id
                && $event->error === 'API down'
                && $event->collector === 'failing';
        });
    }

    public function test_collect_exception_marks_item_failed_and_leaves_other_items(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $throwing = $this->makeThrowingCollector();

        $this->app->instance(
            IntegrationSyncDispatcher::class,
            new IntegrationSyncDispatcher([$throwing])
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
            'collector' => 'throwing',
            'status' => IntegrationSyncItemStatus::Pending,
            'attempts' => 0,
            'position' => 0,
        ]);

        $neighbor = IntegrationSyncItem::query()->create([
            'run_id' => $run->id,
            'project_id' => $project->id,
            'collector' => 'other',
            'status' => IntegrationSyncItemStatus::Pending,
            'attempts' => 0,
            'position' => 1,
        ]);

        (new ProcessIntegrationSyncItem($item->id))->handle(app(IntegrationSyncDispatcher::class));

        $item->refresh();
        $neighbor->refresh();
        $run->refresh();

        $this->assertSame(IntegrationSyncItemStatus::Failed, $item->status);
        $this->assertSame('boom', $item->last_error);
        $this->assertSame(IntegrationSyncItemStatus::Pending, $neighbor->status);
        $this->assertSame(IntegrationSyncRunStatus::Running, $run->status);
        Event::assertDispatched(IntegrationSyncFailed::class);
    }

    public function test_collect_exception_strips_log_permission_wrapper_from_last_error(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $throwing = new class implements IntegrationSyncCollector
        {
            public function key(): string
            {
                return 'throwing';
            }

            public function integrationCode(): string
            {
                return 'throwing';
            }

            public function supportsProject(int $projectId): bool
            {
                return true;
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                throw new RuntimeException(
                    'The stream or file "/tmp/x.log" could not be opened in append mode: Failed to open stream: Permission denied'
                    ."\nThe exception occurred while attempting to log: Failed to get daily expenses"
                );
            }

            public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
            {
                return IntegrationSyncResult::failure('unused', requeue: false);
            }
        };

        $this->app->instance(
            IntegrationSyncDispatcher::class,
            new IntegrationSyncDispatcher([$throwing])
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
            'collector' => 'throwing',
            'status' => IntegrationSyncItemStatus::Pending,
            'attempts' => 0,
            'position' => 0,
        ]);

        (new ProcessIntegrationSyncItem($item->id))->handle(app(IntegrationSyncDispatcher::class));

        $item->refresh();
        $this->assertSame('Failed to get daily expenses', $item->last_error);
    }

    public function test_collect_exception_finishes_run_when_last_item(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $throwing = $this->makeThrowingCollector();

        $this->app->instance(
            IntegrationSyncDispatcher::class,
            new IntegrationSyncDispatcher([$throwing])
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
            'collector' => 'throwing',
            'status' => IntegrationSyncItemStatus::Pending,
            'attempts' => 0,
            'position' => 0,
        ]);

        (new ProcessIntegrationSyncItem($item->id))->handle(app(IntegrationSyncDispatcher::class));

        $item->refresh();
        $run->refresh();

        $this->assertSame(IntegrationSyncItemStatus::Failed, $item->status);
        $this->assertSame(IntegrationSyncRunStatus::Failed, $run->status);
        $this->assertNotNull($run->finished_at);
    }

    public function test_failed_callback_marks_processing_item_failed(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

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
            'status' => IntegrationSyncItemStatus::Processing,
            'attempts' => 1,
            'position' => 0,
        ]);

        (new ProcessIntegrationSyncItem($item->id))->failed(new RuntimeException('queue boom'));

        $item->refresh();
        $run->refresh();

        $this->assertSame(IntegrationSyncItemStatus::Failed, $item->status);
        $this->assertSame('queue boom', $item->last_error);
        $this->assertSame(IntegrationSyncRunStatus::Failed, $run->status);
        Event::assertDispatched(IntegrationSyncFailed::class);
    }

    private function makeFailingCollector(): IntegrationSyncCollector
    {
        return new class implements IntegrationSyncCollector
        {
            public function key(): string
            {
                return 'failing';
            }

            public function integrationCode(): string
            {
                return 'failing';
            }

            public function supportsProject(int $projectId): bool
            {
                return true;
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                return IntegrationSyncResult::failure('API down', requeue: true);
            }

            public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
            {
                return IntegrationSyncResult::failure('API down', requeue: true);
            }
        };
    }

    private function makeThrowingCollector(): IntegrationSyncCollector
    {
        return new class implements IntegrationSyncCollector
        {
            public function key(): string
            {
                return 'throwing';
            }

            public function integrationCode(): string
            {
                return 'throwing';
            }

            public function supportsProject(int $projectId): bool
            {
                return true;
            }

            public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
            {
                throw new RuntimeException('boom');
            }

            public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
            {
                throw new RuntimeException('boom');
            }
        };
    }
}
