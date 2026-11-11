<?php

namespace App\Jobs;

use App\Services\IntegrationSync\IntegrationManualRefreshNotifier;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use App\Services\IntegrationSync\IntegrationMetricsRefreshService;
use App\Services\Statistics\StatisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshReportIntegrationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public int $userId,
        public int $agencyId,
        public string $product,
        public int $projectId,
        public int $projectIndex,
        public string $periodFrom,
        public string $periodTo,
        public bool $includeVat,
        public bool $withDirectBudget,
        public bool $recalculateBonuses,
        public string $runId,
    ) {}

    public function handle(
        IntegrationMetricsRefreshService $metricsRefreshService,
        IntegrationManualRefreshProgress $progress,
        IntegrationManualRefreshTimestamp $timestamps,
        IntegrationManualRefreshNotifier $notifier,
        StatisticsService $statisticsService,
    ): void {
        $state = $progress->get($this->agencyId);

        if ($state === null
            || ($state['run_id'] ?? null) !== $this->runId
            || ($state['status'] ?? null) !== IntegrationManualRefreshProgress::STATUS_RUNNING
        ) {
            return;
        }

        $stillPending = collect($state['pending'] ?? [])
            ->contains(fn (array $item): bool => (int) ($item['id'] ?? 0) === $this->projectId);

        if (! $stillPending) {
            return;
        }

        try {
            $outcome = $metricsRefreshService->refreshSingleProject(
                $this->agencyId,
                $this->projectId,
                $this->projectIndex,
                Carbon::parse($this->periodFrom)->startOfDay(),
                Carbon::parse($this->periodTo)->startOfDay(),
                $this->withDirectBudget,
            );
        } catch (Throwable $e) {
            Log::warning('Manual refresh job failed', [
                'project_id' => $this->projectId,
                'agency_id' => $this->agencyId,
                'product' => $this->product,
                'message' => $e->getMessage(),
            ]);
            $outcome = 'failed';
        }

        $after = $progress->markProjectFinished(
            $this->agencyId,
            $this->projectId,
            $outcome,
        );

        if ($after === null) {
            return;
        }

        if (($after['pending'] ?? []) !== []) {
            return;
        }

        $this->finalizeRun(
            $progress,
            $timestamps,
            $notifier,
            $statisticsService,
            $after,
        );
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('Manual refresh job permanently failed', [
            'project_id' => $this->projectId,
            'agency_id' => $this->agencyId,
            'product' => $this->product,
            'run_id' => $this->runId,
            'message' => $e?->getMessage(),
        ]);

        $progress = app(IntegrationManualRefreshProgress::class);
        $state = $progress->get($this->agencyId);

        if ($state === null
            || ($state['run_id'] ?? null) !== $this->runId
            || ($state['status'] ?? null) !== IntegrationManualRefreshProgress::STATUS_RUNNING
        ) {
            return;
        }

        $after = $progress->markProjectFinished(
            $this->agencyId,
            $this->projectId,
            'failed',
        );

        if ($after === null) {
            return;
        }

        if (($after['pending'] ?? []) !== []) {
            return;
        }

        $this->finalizeRun(
            $progress,
            app(IntegrationManualRefreshTimestamp::class),
            app(IntegrationManualRefreshNotifier::class),
            app(StatisticsService::class),
            $after,
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function finalizeRun(
        IntegrationManualRefreshProgress $progress,
        IntegrationManualRefreshTimestamp $timestamps,
        IntegrationManualRefreshNotifier $notifier,
        StatisticsService $statisticsService,
        array $state,
    ): void {
        if ($this->recalculateBonuses && $this->product === 'statistics') {
            $from = Carbon::parse($this->periodFrom)->startOfMonth()->startOfDay();
            $to = Carbon::parse($this->periodTo)->startOfMonth()->startOfDay();

            if ($from->equalTo($to)) {
                $progress->setCurrentStep(
                    $this->agencyId,
                    $this->projectId,
                    'bonuses',
                    (int) ($state['total'] ?? $this->projectIndex),
                );

                $allIds = array_values(array_map(
                    static fn ($id): int => (int) $id,
                    $state['all_ids'] ?? [],
                ));

                if ($allIds !== []) {
                    $statisticsService->recalculateMonthlyBonuses(
                        $allIds,
                        $from,
                        $this->includeVat,
                    );
                }
            }
        }

        $progress->markDone($this->agencyId);
        $timestamps->record($this->agencyId);

        $notifier->notifyAgency(
            agencyId: $this->agencyId,
            fallbackUserId: $this->userId,
            startedProduct: $this->product,
            runId: $this->runId,
            updated: (int) ($state['updated'] ?? 0),
            failed: (int) ($state['failed'] ?? 0),
            skipped: (int) ($state['skipped'] ?? 0),
        );
    }
}
