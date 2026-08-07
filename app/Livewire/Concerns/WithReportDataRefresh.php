<?php

namespace App\Livewire\Concerns;

use App\Data\TableReportData;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use App\Services\IntegrationSync\IntegrationMetricsRefreshService;
use Illuminate\Support\Facades\Auth;

trait WithReportDataRefresh
{
    public ?string $lastDataRefreshLabel = null;

    public function mountWithReportDataRefresh(IntegrationManualRefreshTimestamp $timestamps): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $this->lastDataRefreshLabel = $timestamps->formattedLabel(
            (int) $userId,
            $this->reportRefreshProductKey(),
        );
    }

    public function refreshAllData(
        IntegrationMetricsRefreshService $metricsRefreshService,
        IntegrationManualRefreshTimestamp $timestamps,
    ): void {
        $projectIds = $this->visibleReportProjectIds();

        if ($projectIds === []) {
            $this->setActionMessage('Нет клиенто-проектов для обновления', 'error');

            return;
        }

        $stats = $metricsRefreshService->refreshReportData(
            $projectIds,
            $this->queryData->dateFrom,
            $this->queryData->dateTo,
            $this->queryData->includeVat,
            $this->shouldRefreshDirectBudget(),
        );

        unset($this->reportData);

        if (! empty($stats['error'])) {
            $this->setActionMessage($stats['error'], 'error');

            return;
        }

        $userId = Auth::id();
        if ($userId !== null) {
            $timestamps->record((int) $userId, $this->reportRefreshProductKey());
            $this->lastDataRefreshLabel = $timestamps->formattedLabel(
                (int) $userId,
                $this->reportRefreshProductKey(),
            );
        }

        $this->setActionMessage(
            sprintf(
                'Обновлено: %d, ошибок: %d, пропущено: %d',
                $stats['updated'],
                $stats['failed'],
                $stats['skipped'],
            ),
            $stats['failed'] > 0 ? 'error' : 'success',
        );
    }

    /**
     * @return list<int>
     */
    protected function visibleReportProjectIds(): array
    {
        /** @var TableReportData $reportData */
        $reportData = $this->reportData;

        return $reportData->groups
            ->flatMap(fn ($group) => $group->rows->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    abstract protected function reportRefreshProductKey(): string;

    abstract protected function shouldRefreshDirectBudget(): bool;
}
