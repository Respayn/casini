<?php

namespace App\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Events\Notifications\IntegrationSyncFailed;
use App\Jobs\RefreshReportIntegrationsJob;
use App\Services\Channels\ChannelDirectMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class IntegrationMetricsRefreshService
{
    public function __construct(
        private readonly IntegrationSyncDispatcher $dispatcher,
        private readonly IntegrationApiThrottle $apiThrottle,
        private readonly ChannelDirectMetricsService $directMetricsService,
        private readonly IntegrationManualRefreshProgress $progress,
    ) {}

    /**
     * Обновление всех проектов текущего отчёта: collectors (+ бюджет Директа в Каналах).
     * Один consume throttle на клик; лимит 50 проектов не применяется.
     *
     * @param  list<int|string>  $projectIds
     * @return array{updated: int, failed: int, skipped: int, error?: string}
     *
     * @deprecated Синхронный путь оставлен для совместимости тестов; UI ставит в очередь через queueReportDataRefresh.
     */
    public function refreshReportData(
        array $projectIds,
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $includeVat = false,
        bool $withDirectBudget = false,
    ): array {
        return $this->refreshProjects(
            $this->normalizeProjectIds($projectIds),
            $periodFrom,
            $periodTo,
            $withDirectBudget,
            consumeThrottle: true,
        );
    }

    /**
     * Поставить ручное обновление в фон: по одной задаче на клиенто-проект.
     *
     * @param  list<array{id: int, label: string, group: ?string}>  $pendingProjects
     * @return array{ok: bool, error?: string, run_id?: string}
     */
    public function queueReportDataRefresh(
        int $userId,
        int $agencyId,
        string $product,
        array $pendingProjects,
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $includeVat,
        bool $withDirectBudget,
        bool $recalculateBonuses = false,
    ): array {
        $ids = $this->normalizeProjectIds(array_column($pendingProjects, 'id'));

        if ($ids === []) {
            return ['ok' => false, 'error' => 'Нет клиенто-проектов для обновления'];
        }

        if ($agencyId <= 0) {
            return ['ok' => false, 'error' => 'Не выбрано агентство'];
        }

        if ($this->progress->isRunning($agencyId)) {
            return ['ok' => false, 'error' => 'Обновление уже выполняется'];
        }

        $throttle = $this->apiThrottle->consume($userId);
        if (! $throttle['ok']) {
            return [
                'ok' => false,
                'error' => $throttle['error'] ?? 'Лимит API',
            ];
        }

        $pendingById = [];
        foreach ($pendingProjects as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && isset(array_flip($ids)[$id])) {
                $pendingById[$id] = [
                    'id' => $id,
                    'label' => (string) ($item['label'] ?? ('№'.$id)),
                    'group' => isset($item['group']) ? (is_string($item['group']) ? $item['group'] : null) : null,
                ];
            }
        }

        $orderedPending = [];
        foreach ($ids as $id) {
            $orderedPending[] = $pendingById[$id] ?? [
                'id' => $id,
                'label' => '№'.$id,
                'group' => null,
            ];
        }

        $periodFromKey = $periodFrom->copy()->startOfMonth()->toDateString();
        $periodToKey = $periodTo->copy()->startOfMonth()->toDateString();
        $state = $this->progress->start(
            $agencyId,
            $product,
            $orderedPending,
            $periodFromKey,
            $periodToKey,
            $includeVat,
        );
        [$from, $to] = $this->resolvePeriod($periodFrom, $periodTo);

        foreach ($orderedPending as $index => $item) {
            RefreshReportIntegrationsJob::dispatch(
                userId: $userId,
                agencyId: $agencyId,
                product: $product,
                projectId: (int) $item['id'],
                projectIndex: $index + 1,
                periodFrom: $from->toDateString(),
                periodTo: $to->toDateString(),
                includeVat: $includeVat,
                withDirectBudget: $withDirectBudget,
                recalculateBonuses: $recalculateBonuses,
                runId: $state['run_id'],
            );
        }

        return [
            'ok' => true,
            'run_id' => $state['run_id'],
        ];
    }

    /**
     * Съём одного проекта без повторного consume (фоновая задача).
     *
     * @return 'updated'|'failed'|'skipped'
     */
    public function refreshSingleProject(
        int $agencyId,
        int $projectId,
        int $projectIndex,
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $withDirectBudget,
    ): string {
        [$from, $to] = $this->resolvePeriod($periodFrom, $periodTo);
        $collectors = $this->dispatcher->collectors();
        $applicable = $this->collectorsForProject($collectors, $projectId);

        if ($applicable === [] && ! $withDirectBudget) {
            return 'skipped';
        }

        $allOk = true;
        $hadFailure = false;
        $hadWork = false;

        foreach ($applicable as $collector) {
            $hadWork = true;
            $this->progress->setCurrentStep(
                $agencyId,
                $projectId,
                $collector->key(),
                $projectIndex,
            );

            try {
                $result = $collector->collectRange($projectId, $from, $to);
            } catch (Throwable $e) {
                Log::warning('Integration refresh: collectRange threw', [
                    'project_id' => $projectId,
                    'collector' => $collector->key(),
                    'message' => $e->getMessage(),
                ]);

                $result = IntegrationSyncResult::failure(
                    filled($e->getMessage()) ? $e->getMessage() : 'Ошибка съёма данных',
                    requeue: false,
                );
            }

            if ($result->ok) {
                continue;
            }

            $allOk = false;
            $hadFailure = true;
            event(new IntegrationSyncFailed(
                projectId: $projectId,
                error: filled($result->error) ? $result->error : 'Ошибка съёма данных',
                collector: $collector->key(),
            ));
        }

        if ($withDirectBudget) {
            $hadWork = true;
            $this->progress->setCurrentStep(
                $agencyId,
                $projectId,
                'direct_budget',
                $projectIndex,
            );
            $this->directMetricsService->refreshBudgetsForcedWithoutThrottle([$projectId]);
        }

        if (! $hadWork) {
            return 'skipped';
        }

        if ($allOk) {
            return 'updated';
        }

        return $hadFailure ? 'failed' : 'skipped';
    }

    /**
     * @param  list<int>  $ids
     * @return array{updated: int, failed: int, skipped: int, error?: string}
     */
    private function refreshProjects(
        array $ids,
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $withDirectBudget,
        bool $consumeThrottle,
    ): array {
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0];

        if ($consumeThrottle) {
            $throttle = $this->apiThrottle->consume();
            if (! $throttle['ok']) {
                return [
                    'updated' => 0,
                    'failed' => count($ids),
                    'skipped' => 0,
                    'error' => $throttle['error'] ?? 'Лимит API',
                ];
            }
        }

        [$from, $to] = $this->resolvePeriod($periodFrom, $periodTo);
        $collectors = $this->dispatcher->collectors();

        foreach ($ids as $projectId) {
            $applicable = $this->collectorsForProject($collectors, $projectId);

            if ($applicable === []) {
                $stats['skipped']++;

                continue;
            }

            $allOk = true;
            $hadFailure = false;

            foreach ($applicable as $collector) {
                try {
                    $result = $collector->collectRange($projectId, $from, $to);
                } catch (Throwable $e) {
                    Log::warning('Integration refresh: collectRange threw', [
                        'project_id' => $projectId,
                        'collector' => $collector->key(),
                        'message' => $e->getMessage(),
                    ]);

                    $result = IntegrationSyncResult::failure(
                        filled($e->getMessage()) ? $e->getMessage() : 'Ошибка съёма данных',
                        requeue: false,
                    );
                }

                if ($result->ok) {
                    continue;
                }

                $allOk = false;
                $hadFailure = true;
                event(new IntegrationSyncFailed(
                    projectId: $projectId,
                    error: filled($result->error) ? $result->error : 'Ошибка съёма данных',
                    collector: $collector->key(),
                ));
            }

            if ($allOk) {
                $stats['updated']++;
            } elseif ($hadFailure) {
                $stats['failed']++;
            }
        }

        if ($withDirectBudget && $ids !== []) {
            $this->directMetricsService->refreshBudgetsForcedWithoutThrottle($ids);
        }

        return $stats;
    }

    /**
     * @param  array<int, IntegrationSyncCollector>  $collectors
     * @return list<IntegrationSyncCollector>
     */
    private function collectorsForProject(array $collectors, int $projectId): array
    {
        $applicable = [];

        foreach ($collectors as $collector) {
            try {
                if ($collector->supportsProject($projectId)) {
                    $applicable[] = $collector;
                }
            } catch (Throwable $e) {
                Log::warning('Integration refresh: supportsProject failed', [
                    'collector' => $collector->key(),
                    'project_id' => $projectId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $applicable;
    }

    /**
     * @param  list<int|string>  $projectIds
     * @return list<int>
     */
    private function normalizeProjectIds(array $projectIds): array
    {
        $unique = [];
        foreach ($projectIds as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $unique[$intId] = $intId;
            }
        }

        return array_values($unique);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Carbon $periodFrom, Carbon $periodTo): array
    {
        $from = $periodFrom->copy()->startOfMonth()->startOfDay();
        $to = $periodTo->copy()->endOfMonth()->startOfDay();
        $today = Carbon::today();

        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            $from = $to->copy();
        }

        return [$from, $to];
    }
}
