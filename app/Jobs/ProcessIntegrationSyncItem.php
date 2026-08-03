<?php

namespace App\Jobs;

use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Enums\IntegrationSyncItemStatus;
use App\Enums\IntegrationSyncRunStatus;
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessIntegrationSyncItem implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Retries управляем в integration_sync_items; job не должен сам ретраиться бесконечно.
     */
    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $itemId,
    ) {}

    public function handle(IntegrationSyncDispatcher $dispatcher): void
    {
        $item = IntegrationSyncItem::query()->find($this->itemId);

        if ($item === null) {
            return;
        }

        if ($item->status === IntegrationSyncItemStatus::Done
            || $item->status === IntegrationSyncItemStatus::Failed
        ) {
            $this->maybeFinishRun($item->run_id);

            return;
        }

        $collector = $dispatcher->collector($item->collector);

        if ($collector === null) {
            $item->update([
                'status' => IntegrationSyncItemStatus::Failed,
                'last_error' => 'Unknown collector: '.$item->collector,
            ]);
            $this->maybeFinishRun($item->run_id);

            return;
        }

        $run = $item->run;
        $item->update([
            'status' => IntegrationSyncItemStatus::Processing,
            'attempts' => $item->attempts + 1,
        ]);

        $result = $collector->collect(new IntegrationSyncCollectContext(
            projectId: (int) $item->project_id,
            targetDate: $run->target_date->copy(),
        ));

        if ($result->ok) {
            $item->update([
                'status' => IntegrationSyncItemStatus::Done,
                'last_error' => null,
            ]);
            $this->maybeFinishRun($item->run_id);

            return;
        }

        $shouldRequeue = $result->requeue
            && $item->attempts < IntegrationSyncDispatcher::MAX_ATTEMPTS;

        if ($shouldRequeue) {
            $this->requeueToEnd($item, $result->error);
            ProcessIntegrationSyncItem::dispatch($item->id);

            return;
        }

        $item->update([
            'status' => IntegrationSyncItemStatus::Failed,
            'last_error' => $result->error,
        ]);
        $this->maybeFinishRun($item->run_id);
    }

    private function requeueToEnd(IntegrationSyncItem $item, ?string $error): void
    {
        DB::transaction(function () use ($item, $error) {
            $maxPosition = (int) IntegrationSyncItem::query()
                ->where('run_id', $item->run_id)
                ->max('position');

            $item->update([
                'status' => IntegrationSyncItemStatus::Pending,
                'position' => $maxPosition + 1,
                'last_error' => $error,
            ]);
        });

        Log::info('Integration sync item requeued', [
            'item_id' => $item->id,
            'project_id' => $item->project_id,
            'attempts' => $item->fresh()->attempts,
        ]);
    }

    private function maybeFinishRun(int $runId): void
    {
        $hasOpen = IntegrationSyncItem::query()
            ->where('run_id', $runId)
            ->whereIn('status', [
                IntegrationSyncItemStatus::Pending->value,
                IntegrationSyncItemStatus::Processing->value,
            ])
            ->exists();

        if ($hasOpen) {
            return;
        }

        $hasFailed = IntegrationSyncItem::query()
            ->where('run_id', $runId)
            ->where('status', IntegrationSyncItemStatus::Failed)
            ->exists();

        IntegrationSyncRun::query()->whereKey($runId)->update([
            'status' => $hasFailed
                ? IntegrationSyncRunStatus::Failed
                : IntegrationSyncRunStatus::Completed,
            'finished_at' => now(),
        ]);
    }
}
