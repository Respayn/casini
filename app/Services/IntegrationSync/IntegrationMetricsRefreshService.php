<?php

namespace App\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Events\Notifications\IntegrationSyncFailed;
use App\Services\Channels\ChannelDirectMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class IntegrationMetricsRefreshService
{
    private const BULK_MAX_PROJECTS = 50;

    public function __construct(
        private readonly IntegrationSyncDispatcher $dispatcher,
        private readonly IntegrationApiThrottle $apiThrottle,
        private readonly ChannelDirectMetricsService $directMetricsService,
    ) {}

    /**
     * Ручное обновление данных по всем collectors, поддерживающим выбранные проекты.
     *
     * @param  list<int|string>  $projectIds
     * @return array{updated: int, failed: int, skipped: int, error?: string}
     */
    public function refreshDataForProjects(
        array $projectIds,
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $includeVat = false,
    ): array {
        return $this->refreshProjects(
            $this->limitProjectIds($projectIds),
            $periodFrom,
            $periodTo,
            withDirectBudget: false,
        );
    }

    /**
     * Обновление всех проектов текущего отчёта: collectors (+ бюджет Директа в Каналах).
     * Один consume throttle на клик; лимит 50 проектов не применяется.
     *
     * @param  list<int|string>  $projectIds
     * @return array{updated: int, failed: int, skipped: int, error?: string}
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
        );
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
    ): array {
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0];

        $throttle = $this->apiThrottle->consume();
        if (! $throttle['ok']) {
            return [
                'updated' => 0,
                'failed' => count($ids),
                'skipped' => 0,
                'error' => $throttle['error'] ?? 'Лимит API',
            ];
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
    private function limitProjectIds(array $projectIds): array
    {
        return array_slice($this->normalizeProjectIds($projectIds), 0, self::BULK_MAX_PROJECTS);
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
