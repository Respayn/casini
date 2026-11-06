<?php

namespace App\Jobs;

use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use App\Services\IntegrationSync\IntegrationMetricsRefreshService;
use App\Services\NotificationService;
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
        NotificationService $notifications,
        StatisticsService $statisticsService,
    ): void {
        $state = $progress->get($this->userId, $this->product);

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
                $this->userId,
                $this->product,
                $this->projectId,
                $this->projectIndex,
                Carbon::parse($this->periodFrom)->startOfDay(),
                Carbon::parse($this->periodTo)->startOfDay(),
                $this->withDirectBudget,
            );
        } catch (Throwable $e) {
            Log::warning('Manual refresh job failed', [
                'project_id' => $this->projectId,
                'product' => $this->product,
                'message' => $e->getMessage(),
            ]);
            $outcome = 'failed';
        }

        $after = $progress->markProjectFinished(
            $this->userId,
            $this->product,
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
            $notifications,
            $statisticsService,
            $after,
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function finalizeRun(
        IntegrationManualRefreshProgress $progress,
        IntegrationManualRefreshTimestamp $timestamps,
        NotificationService $notifications,
        StatisticsService $statisticsService,
        array $state,
    ): void {
        if ($this->recalculateBonuses && $this->product === 'statistics') {
            $from = Carbon::parse($this->periodFrom)->startOfMonth()->startOfDay();
            $to = Carbon::parse($this->periodTo)->startOfMonth()->startOfDay();

            if ($from->equalTo($to)) {
                $progress->setCurrentStep(
                    $this->userId,
                    $this->product,
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

        $progress->markDone($this->userId, $this->product);
        $timestamps->record($this->userId, $this->product);

        $failed = (int) ($state['failed'] ?? 0);
        $productTitle = IntegrationManualRefreshProgress::productTitle($this->product);
        $text = "Данные в {$productTitle} обновлены";
        if ($failed > 0) {
            $text .= ". Ошибок: {$failed}";
        }

        $routeName = $this->product === 'statistics' ? 'statistics' : 'channels';

        $notifications->create(
            userId: $this->userId,
            text: $text,
            linkUrl: route($routeName),
            links: [],
            type: IntegrationManualRefreshProgress::TYPE_REFRESHED,
            payload: [
                'product' => $this->product,
                'updated' => (int) ($state['updated'] ?? 0),
                'failed' => $failed,
                'skipped' => (int) ($state['skipped'] ?? 0),
                'run_id' => $this->runId,
            ],
        );
    }
}
