<?php

namespace App\Livewire\Concerns;

use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use App\Services\IntegrationSync\IntegrationMetricsRefreshService;
use Illuminate\Support\Facades\Auth;

trait WithReportDataRefresh
{
    public ?string $lastDataRefreshLabel = null;

    public bool $isReportDataRefreshing = false;

    /** @var list<array{id: int, label: string, group: ?string}> */
    public array $refreshPendingProjects = [];

    public int $refreshTotal = 0;

    public int $refreshCurrentIndex = 0;

    public ?string $refreshProgressLabel = null;

    public ?string $refreshProgressProject = null;

    public ?int $refreshStepStartedAt = null;

    public ?string $refreshPeriodFrom = null;

    public ?string $refreshPeriodTo = null;

    public bool $refreshIncludeVat = false;

    public function mountWithReportDataRefresh(
        IntegrationManualRefreshTimestamp $timestamps,
        IntegrationManualRefreshProgress $progress,
    ): void {
        $userId = Auth::id();
        $agencyId = $this->currentRefreshAgencyId();

        if ($userId === null || $agencyId === null) {
            return;
        }

        $this->lastDataRefreshLabel = $timestamps->formattedLabel($agencyId);
        $this->hydrateRefreshProgressFromCache($progress, $agencyId, (int) $userId);
    }

    public function refreshAllData(
        IntegrationMetricsRefreshService $metricsRefreshService,
        IntegrationManualRefreshProgress $progress,
    ): void {
        $userId = Auth::id();
        $agencyId = $this->currentRefreshAgencyId();

        if ($userId === null) {
            $this->setActionMessage('Нужна авторизация', 'error');

            return;
        }

        if ($agencyId === null) {
            $this->setActionMessage('Не выбрано агентство', 'error');

            return;
        }

        if ($this->isReportDataRefreshing || $progress->isRunning($agencyId)) {
            return;
        }

        $pending = $this->buildRefreshPendingProjects();

        if ($pending === []) {
            $this->setActionMessage('Нет клиенто-проектов для обновления', 'error');

            return;
        }

        $product = $this->reportRefreshProductKey();
        $queued = $metricsRefreshService->queueReportDataRefresh(
            userId: (int) $userId,
            agencyId: $agencyId,
            product: $product,
            pendingProjects: $pending,
            periodFrom: $this->queryData->dateFrom,
            periodTo: $this->queryData->dateTo,
            includeVat: (bool) $this->queryData->includeVat,
            withDirectBudget: $this->shouldRefreshDirectBudget(),
            recalculateBonuses: $product === 'statistics' && $this->queryData->isSingleMonthPeriod(),
        );

        if (! ($queued['ok'] ?? false)) {
            $this->setActionMessage($queued['error'] ?? 'Не удалось запустить обновление', 'error');

            return;
        }

        $this->isReportDataRefreshing = true;
        $this->refreshPendingProjects = $pending;
        $this->refreshTotal = count($pending);
        $this->refreshCurrentIndex = 0;
        $this->refreshProgressLabel = null;
        $this->refreshProgressProject = null;
        $this->refreshStepStartedAt = time();
        $this->refreshPeriodFrom = $this->queryData->dateFrom->copy()->startOfMonth()->toDateString();
        $this->refreshPeriodTo = $this->queryData->dateTo->copy()->startOfMonth()->toDateString();
        $this->refreshIncludeVat = (bool) $this->queryData->includeVat;
        unset($this->reportData);
    }

    public function pollRefreshProgress(
        IntegrationManualRefreshProgress $progress,
        IntegrationManualRefreshTimestamp $timestamps,
    ): void {
        $userId = Auth::id();
        $agencyId = $this->currentRefreshAgencyId();

        if ($userId === null || $agencyId === null) {
            return;
        }

        $state = $progress->get($agencyId);

        if ($state === null) {
            if ($this->isReportDataRefreshing) {
                $this->clearRefreshUiState();
                unset($this->reportData);
            }

            return;
        }

        $pendingBefore = array_map(
            static fn (array $item): int => (int) $item['id'],
            $this->refreshPendingProjects,
        );

        $this->applyProgressState($state);

        $pendingAfter = array_map(
            static fn (array $item): int => (int) $item['id'],
            $this->refreshPendingProjects,
        );

        if ($pendingBefore !== $pendingAfter) {
            unset($this->reportData);
        }

        if (($state['status'] ?? null) === IntegrationManualRefreshProgress::STATUS_DONE) {
            $this->finishRefreshFromProgress(
                $progress,
                $timestamps,
                $state,
                $agencyId,
                (int) $userId,
            );
        }
    }

    public function cancelReportDataRefresh(): void
    {
        // Фоновый съём не отменяем: задачи уже в очереди.
    }

    public function isRefreshRowPending(int $rowId): bool
    {
        if (! $this->matchesRefreshSlice() || $rowId <= 0) {
            return false;
        }

        foreach ($this->refreshPendingProjects as $item) {
            if ((int) ($item['id'] ?? 0) === $rowId) {
                return true;
            }
        }

        return false;
    }

    public function isRefreshGroupPending(TableReportGroupData $group): bool
    {
        if (! $this->matchesRefreshSlice()) {
            return false;
        }

        foreach ($group->rows as $row) {
            if ($this->isRefreshRowPending((int) $row->id)) {
                return true;
            }
        }

        return false;
    }

    public function isRefreshTableSummaryPending(): bool
    {
        return $this->matchesRefreshSlice() && $this->refreshPendingProjects !== [];
    }

    /**
     * @param  list<int>  $projectIds
     */
    protected function afterSuccessfulReportDataRefresh(array $projectIds): void {}

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

    /**
     * @return list<array{id: int, label: string, group: ?string}>
     */
    protected function buildRefreshPendingProjects(): array
    {
        /** @var TableReportData $reportData */
        $reportData = $this->reportData;
        $pending = [];

        foreach ($reportData->groups as $group) {
            $groupLabel = filled($group->groupLabel) ? (string) $group->groupLabel : null;

            foreach ($group->rows as $row) {
                $id = (int) $row->id;
                if ($id <= 0) {
                    continue;
                }

                $pending[] = [
                    'id' => $id,
                    'label' => $this->refreshRowLabel($row),
                    'group' => $groupLabel,
                ];
            }
        }

        return $pending;
    }

    protected function refreshRowLabel(TableReportRowData $row): string
    {
        $clientProject = $row->data->get('client-project');

        if (is_array($clientProject) && filled($clientProject['name'] ?? null)) {
            return (string) $clientProject['name'];
        }

        return '№'.$row->id;
    }

    protected function matchesRefreshSlice(): bool
    {
        if (! $this->isReportDataRefreshing
            || $this->refreshPeriodFrom === null
            || $this->refreshPeriodTo === null
        ) {
            return false;
        }

        return $this->queryData->dateFrom->copy()->startOfMonth()->toDateString() === $this->refreshPeriodFrom
            && $this->queryData->dateTo->copy()->startOfMonth()->toDateString() === $this->refreshPeriodTo
            && (bool) $this->queryData->includeVat === $this->refreshIncludeVat;
    }

    protected function currentRefreshAgencyId(): ?int
    {
        $sessionAgencyId = session('current_agency_id');

        if (filled($sessionAgencyId) && (int) $sessionAgencyId > 0) {
            return (int) $sessionAgencyId;
        }

        $fallback = Auth::user()?->agencies()->value('agencies.id');

        return filled($fallback) && (int) $fallback > 0
            ? (int) $fallback
            : null;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function applyProgressState(array $state): void
    {
        $this->refreshPeriodFrom = isset($state['period_from']) && is_string($state['period_from'])
            ? $state['period_from']
            : null;
        $this->refreshPeriodTo = isset($state['period_to']) && is_string($state['period_to'])
            ? $state['period_to']
            : null;
        $this->refreshIncludeVat = (bool) ($state['include_vat'] ?? false);

        $isRunning = ($state['status'] ?? null) === IntegrationManualRefreshProgress::STATUS_RUNNING;

        if (! $isRunning) {
            $this->isReportDataRefreshing = false;
            $this->refreshPendingProjects = [];
            $this->refreshTotal = 0;
            $this->refreshCurrentIndex = 0;
            $this->refreshProgressLabel = null;
            $this->refreshProgressProject = null;
            $this->refreshStepStartedAt = null;

            return;
        }

        if (! $this->refreshStateTouchesVisibleProjects($state)) {
            $this->clearRefreshUiState();

            return;
        }

        $visibleIds = $this->visibleRefreshProjectIdSet();
        $pending = [];

        foreach (array_values($state['pending'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && isset($visibleIds[$id])) {
                $pending[] = $item;
            }
        }

        $currentProjectId = isset($state['current_project_id']) ? (int) $state['current_project_id'] : 0;
        $currentIsVisible = $currentProjectId > 0 && isset($visibleIds[$currentProjectId]);

        $this->isReportDataRefreshing = true;
        $this->refreshPendingProjects = $pending;
        $this->refreshStepStartedAt = isset($state['step_started_at'])
            ? (int) $state['step_started_at']
            : null;

        if ($currentIsVisible) {
            $this->refreshTotal = (int) ($state['total'] ?? count($state['pending'] ?? []));
            $this->refreshCurrentIndex = (int) ($state['current_index'] ?? 0);
            $this->refreshProgressLabel = isset($state['current_label']) && is_string($state['current_label'])
                ? $state['current_label']
                : null;
            $this->refreshProgressProject = isset($state['current_project_label']) && is_string($state['current_project_label'])
                ? $state['current_project_label']
                : null;

            return;
        }

        $this->refreshTotal = 0;
        $this->refreshCurrentIndex = 0;
        $this->refreshProgressLabel = null;
        $this->refreshProgressProject = null;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<int>
     */
    private function refreshStateProjectIds(array $state): array
    {
        $ids = [];

        foreach ($state['all_ids'] ?? [] as $id) {
            $ids[] = (int) $id;
        }

        if ($ids === []) {
            foreach ($state['pending'] ?? [] as $item) {
                if (is_array($item)) {
                    $ids[] = (int) ($item['id'] ?? 0);
                }
            }

            if (isset($state['current_project_id'])) {
                $ids[] = (int) $state['current_project_id'];
            }
        }

        return array_values(array_unique(array_filter(
            $ids,
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @return array<int, true>
     */
    private function visibleRefreshProjectIdSet(): array
    {
        return array_fill_keys($this->visibleReportProjectIds(), true);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function refreshStateTouchesVisibleProjects(array $state): bool
    {
        $visibleIds = $this->visibleRefreshProjectIdSet();

        if ($visibleIds === []) {
            return false;
        }

        foreach ($this->refreshStateProjectIds($state) as $id) {
            if (isset($visibleIds[$id])) {
                return true;
            }
        }

        return false;
    }

    private function clearRefreshUiState(): void
    {
        $this->isReportDataRefreshing = false;
        $this->refreshPendingProjects = [];
        $this->refreshProgressLabel = null;
        $this->refreshProgressProject = null;
        $this->refreshStepStartedAt = null;
        $this->refreshCurrentIndex = 0;
        $this->refreshPeriodFrom = null;
        $this->refreshPeriodTo = null;
        $this->refreshIncludeVat = false;
    }

    private function hydrateRefreshProgressFromCache(
        IntegrationManualRefreshProgress $progress,
        int $agencyId,
        int $userId,
    ): void {
        $state = $progress->get($agencyId);

        if ($state === null) {
            return;
        }

        if (($state['status'] ?? null) === IntegrationManualRefreshProgress::STATUS_RUNNING) {
            $this->applyProgressState($state);

            return;
        }

        if (($state['status'] ?? null) === IntegrationManualRefreshProgress::STATUS_DONE
            && ! $progress->wasNoticed($state, $userId, $this->reportRefreshProductKey())
        ) {
            $this->finishRefreshFromProgress(
                $progress,
                app(IntegrationManualRefreshTimestamp::class),
                $state,
                $agencyId,
                $userId,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function finishRefreshFromProgress(
        IntegrationManualRefreshProgress $progress,
        IntegrationManualRefreshTimestamp $timestamps,
        array $state,
        int $agencyId,
        int $userId,
    ): void {
        $product = $this->reportRefreshProductKey();

        if ($progress->wasNoticed($state, $userId, $product)) {
            $this->clearRefreshUiState();

            return;
        }

        if (! $this->refreshStateTouchesVisibleProjects($state)) {
            $this->clearRefreshUiState();
            $progress->markNoticed($agencyId, $userId, $product);

            return;
        }

        $failed = (int) ($state['failed'] ?? 0);
        $this->setActionMessage(
            IntegrationManualRefreshProgress::sharedFinishMessage($failed),
            $failed > 0 ? 'error' : 'success',
        );
        $this->lastDataRefreshLabel = $timestamps->formattedLabel($agencyId);
        $this->clearRefreshUiState();
        $progress->markNoticed($agencyId, $userId, $product);
        unset($this->reportData);
    }

    abstract protected function reportRefreshProductKey(): string;

    abstract protected function shouldRefreshDirectBudget(): bool;
}
