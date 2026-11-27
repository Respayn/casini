<?php

namespace App\Livewire\Concerns;

use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use App\Services\IntegrationSync\IntegrationAutomaticSyncStatus;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use App\Services\IntegrationSync\IntegrationMetricsRefreshService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

trait WithReportDataRefresh
{
    public ?string $lastDataRefreshLabel = null;

    public bool $isReportDataRefreshing = false;

    public bool $isAutomaticDataRefresh = false;

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
        IntegrationAutomaticSyncStatus $automaticSync,
    ): void {
        $userId = Auth::id();
        $agencyId = $this->currentRefreshAgencyId();

        if ($userId === null || $agencyId === null) {
            return;
        }

        $this->lastDataRefreshLabel = $timestamps->formattedLabel($agencyId);
        $this->hydrateRefreshProgressFromCache($progress, $agencyId, (int) $userId);

        if (! $this->isReportDataRefreshing || $this->isAutomaticDataRefresh) {
            $this->hydrateAutomaticSyncStatus($automaticSync);
        }
    }

    public function refreshAllData(
        IntegrationMetricsRefreshService $metricsRefreshService,
        IntegrationManualRefreshProgress $progress,
        IntegrationAutomaticSyncStatus $automaticSync,
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

        if ($automaticSync->isRunning()) {
            $this->setActionMessage(
                'Сейчас идёт автоматический съём данных. Ручное обновление недоступно.',
                'error',
            );

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

        $this->isAutomaticDataRefresh = false;
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
        IntegrationAutomaticSyncStatus $automaticSync,
    ): void {
        $userId = Auth::id();
        $agencyId = $this->currentRefreshAgencyId();

        if ($userId === null || $agencyId === null) {
            return;
        }

        $state = $progress->get($agencyId);
        $manualStatus = is_array($state) ? ($state['status'] ?? null) : null;

        if ($manualStatus === IntegrationManualRefreshProgress::STATUS_RUNNING) {
            $this->isAutomaticDataRefresh = false;

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

            return;
        }

        if ($manualStatus === IntegrationManualRefreshProgress::STATUS_DONE) {
            $this->finishRefreshFromProgress(
                $progress,
                $timestamps,
                $state,
                $agencyId,
                (int) $userId,
            );
        }

        $this->hydrateAutomaticSyncStatus($automaticSync);
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
            || $this->isAutomaticDataRefresh
            || $this->refreshPeriodFrom === null
            || $this->refreshPeriodTo === null
        ) {
            return false;
        }

        return $this->queryData->dateFrom->copy()->startOfMonth()->toDateString() === $this->refreshPeriodFrom
            && $this->queryData->dateTo->copy()->startOfMonth()->toDateString() === $this->refreshPeriodTo
            && (bool) $this->queryData->includeVat === $this->refreshIncludeVat;
    }

    protected function isManualReportDataRefreshing(): bool
    {
        return $this->isReportDataRefreshing && ! $this->isAutomaticDataRefresh;
    }

    /**
     * НДС и период нельзя менять во время ручного съёма: иначе серые полоски пропадут.
     * Возвращает true, если запрос нужно игнорировать (съём идёт).
     */
    protected function restoreLockedRefreshSliceFilters(): bool
    {
        if (! $this->isManualReportDataRefreshing()) {
            return false;
        }

        if ($this->refreshPeriodFrom !== null) {
            $currentFrom = $this->queryData->dateFrom instanceof Carbon
                ? $this->queryData->dateFrom->copy()->startOfMonth()->toDateString()
                : null;

            if ($currentFrom !== $this->refreshPeriodFrom) {
                $this->queryData->dateFrom = Carbon::parse($this->refreshPeriodFrom)->startOfMonth();
            }
        }

        if ($this->refreshPeriodTo !== null) {
            $currentTo = $this->queryData->dateTo instanceof Carbon
                ? $this->queryData->dateTo->copy()->startOfMonth()->toDateString()
                : null;

            if ($currentTo !== $this->refreshPeriodTo) {
                $this->queryData->dateTo = Carbon::parse($this->refreshPeriodTo)->startOfMonth();
            }
        }

        if ((bool) $this->queryData->includeVat !== $this->refreshIncludeVat) {
            $this->queryData->includeVat = $this->refreshIncludeVat;
        }

        return true;
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

    private function hydrateAutomaticSyncStatus(IntegrationAutomaticSyncStatus $automaticSync): void
    {
        $snapshot = $automaticSync->currentSnapshot();

        if ($snapshot === null) {
            if ($this->isAutomaticDataRefresh) {
                $this->clearRefreshUiState();
                unset($this->reportData);
            }

            return;
        }

        if (! $this->refreshStateTouchesVisibleProjects($snapshot)) {
            if ($this->isAutomaticDataRefresh || $this->isReportDataRefreshing) {
                $this->clearRefreshUiState();
            }

            return;
        }

        $wasAutomatic = $this->isAutomaticDataRefresh;
        $this->isAutomaticDataRefresh = true;
        $this->refreshPeriodFrom = null;
        $this->refreshPeriodTo = null;
        $this->refreshIncludeVat = false;

        $visibleIds = $this->visibleRefreshProjectIdSet();
        $currentProjectId = isset($snapshot['current_project_id']) ? (int) $snapshot['current_project_id'] : 0;
        $currentIsVisible = $currentProjectId > 0 && isset($visibleIds[$currentProjectId]);

        $this->isReportDataRefreshing = true;
        $this->refreshPendingProjects = [];
        $this->refreshStepStartedAt = isset($snapshot['step_started_at'])
            ? (int) $snapshot['step_started_at']
            : null;

        if ($currentIsVisible) {
            $this->refreshTotal = (int) ($snapshot['total'] ?? 0);
            $this->refreshCurrentIndex = (int) ($snapshot['current_index'] ?? 0);
            $this->refreshProgressLabel = isset($snapshot['current_label']) && is_string($snapshot['current_label'])
                ? $snapshot['current_label']
                : null;
            $this->refreshProgressProject = isset($snapshot['current_project_label']) && is_string($snapshot['current_project_label'])
                ? $snapshot['current_project_label']
                : null;
        } else {
            $this->refreshTotal = 0;
            $this->refreshCurrentIndex = 0;
            $this->refreshProgressLabel = null;
            $this->refreshProgressProject = null;
        }

        if (! $wasAutomatic) {
            unset($this->reportData);
        }
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
        $this->isAutomaticDataRefresh = false;
        $this->refreshPendingProjects = [];
        $this->refreshProgressLabel = null;
        $this->refreshProgressProject = null;
        $this->refreshStepStartedAt = null;
        $this->refreshCurrentIndex = 0;
        $this->refreshTotal = 0;
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
            $this->isAutomaticDataRefresh = false;
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
        // Job уже записал уведомления в БД; шапка сама не поллит - просим обновить колокольчик.
        $this->dispatch('notifications-changed');
        unset($this->reportData);
    }

    abstract protected function reportRefreshProductKey(): string;

    abstract protected function shouldRefreshDirectBudget(): bool;
}
