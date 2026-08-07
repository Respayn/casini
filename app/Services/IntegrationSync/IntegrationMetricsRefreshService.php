<?php

namespace App\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use Illuminate\Support\Carbon;

class IntegrationMetricsRefreshService
{
    private const BULK_MAX_PROJECTS = 50;

    public function __construct(
        private readonly IntegrationSyncDispatcher $dispatcher,
        private readonly IntegrationApiThrottle $apiThrottle,
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
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0];
        $ids = $this->limitProjectIds($projectIds);

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
            $projectId = (int) $projectId;
            $applicable = array_values(array_filter(
                $collectors,
                fn (IntegrationSyncCollector $collector) => $collector->supportsProject($projectId),
            ));

            if ($applicable === []) {
                $stats['skipped']++;

                continue;
            }

            $allOk = true;
            $hadFailure = false;

            foreach ($applicable as $collector) {
                $result = $collector->collectRange($projectId, $from, $to);

                if (! $result->ok) {
                    $allOk = false;
                    $hadFailure = true;
                }
            }

            if ($allOk) {
                $stats['updated']++;
            } elseif ($hadFailure) {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @param  list<int|string>  $projectIds
     * @return list<int>
     */
    private function limitProjectIds(array $projectIds): array
    {
        $unique = [];
        foreach ($projectIds as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $unique[$intId] = $intId;
            }
        }

        return array_slice(array_values($unique), 0, self::BULK_MAX_PROJECTS);
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
