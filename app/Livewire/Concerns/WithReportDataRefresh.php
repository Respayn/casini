<?php

namespace App\Livewire\Concerns;

use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use App\Models\Notification;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use App\Services\IntegrationSync\IntegrationMetricsRefreshService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

    public ?int $refreshStepStartedAt = null;

    public function mountWithReportDataRefresh(
        IntegrationManualRefreshTimestamp $timestamps,
        IntegrationManualRefreshProgress $progress,
    ): void {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $product = $this->reportRefreshProductKey();

        $this->lastDataRefreshLabel = $timestamps->formattedLabel((int) $userId, $product);
        $this->hydrateRefreshProgressFromCache($progress, (int) $userId, $product);
        $this->consumeUnreadRefreshNotification((int) $userId, $product);
    }

    public function refreshAllData(
        IntegrationMetricsRefreshService $metricsRefreshService,
        IntegrationManualRefreshProgress $progress,
    ): void {
        $userId = Auth::id();

        if ($userId === null) {
            $this->setActionMessage('Нужна авторизация', 'error');

            return;
        }

        if ($this->isReportDataRefreshing) {
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
        $this->refreshStepStartedAt = time();
        unset($this->reportData);
    }

    public function pollRefreshProgress(
        IntegrationManualRefreshProgress $progress,
        IntegrationManualRefreshTimestamp $timestamps,
    ): void {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $product = $this->reportRefreshProductKey();
        $state = $progress->get((int) $userId, $product);

        if ($state === null) {
            if ($this->isReportDataRefreshing) {
                $this->isReportDataRefreshing = false;
                $this->refreshPendingProjects = [];
                $this->refreshProgressLabel = null;
                $this->refreshStepStartedAt = null;
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
            $this->finishRefreshFromProgress($progress, $timestamps, $state, (int) $userId, $product);
        }
    }

    public function cancelReportDataRefresh(): void
    {
        // Фоновый съём не отменяем: задачи уже в очереди.
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

    protected function filterReportDataExcludingPending(TableReportData $reportData): TableReportData
    {
        if (! $this->isReportDataRefreshing || $this->refreshPendingProjects === []) {
            return $reportData;
        }

        $pendingIds = array_flip(array_map(
            static fn (array $item): int => (int) $item['id'],
            $this->refreshPendingProjects,
        ));

        $filtered = new TableReportData;
        $visibleRows = new Collection;

        foreach ($reportData->groups as $group) {
            $rows = $group->rows
                ->filter(static fn (TableReportRowData $row): bool => ! isset($pendingIds[(int) $row->id]))
                ->values();

            if ($rows->isEmpty()) {
                continue;
            }

            $nextGroup = new TableReportGroupData;
            $nextGroup->groupLabel = $group->groupLabel;
            $nextGroup->rows = $rows;
            $nextGroup->summary = $rows->count() === $group->rows->count()
                ? $group->summary
                : null;
            $filtered->groups->push($nextGroup);
            $visibleRows = $visibleRows->concat($rows);
        }

        if ($visibleRows->isEmpty()) {
            $filtered->summary = new Collection;

            return $filtered;
        }

        $filtered->summary = $reportData->summary;

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function applyProgressState(array $state): void
    {
        $this->isReportDataRefreshing = ($state['status'] ?? null) === IntegrationManualRefreshProgress::STATUS_RUNNING;
        $this->refreshPendingProjects = array_values($state['pending'] ?? []);
        $this->refreshTotal = (int) ($state['total'] ?? count($this->refreshPendingProjects));
        $this->refreshCurrentIndex = (int) ($state['current_index'] ?? 0);
        $this->refreshProgressLabel = isset($state['current_label'])
            ? (is_string($state['current_label']) ? $state['current_label'] : null)
            : null;
        $this->refreshStepStartedAt = isset($state['step_started_at'])
            ? (int) $state['step_started_at']
            : null;
    }

    private function hydrateRefreshProgressFromCache(
        IntegrationManualRefreshProgress $progress,
        int $userId,
        string $product,
    ): void {
        $state = $progress->get($userId, $product);

        if ($state === null) {
            return;
        }

        if (($state['status'] ?? null) === IntegrationManualRefreshProgress::STATUS_RUNNING) {
            $this->applyProgressState($state);

            return;
        }

        if (($state['status'] ?? null) === IntegrationManualRefreshProgress::STATUS_DONE
            && empty($state['noticed'])
        ) {
            $this->finishRefreshFromProgress(
                $progress,
                app(IntegrationManualRefreshTimestamp::class),
                $state,
                $userId,
                $product,
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
        int $userId,
        string $product,
    ): void {
        $failed = (int) ($state['failed'] ?? 0);
        $productTitle = IntegrationManualRefreshProgress::productTitle($product);
        $message = "Данные в {$productTitle} обновлены";
        if ($failed > 0) {
            $message .= ". Ошибок: {$failed}";
        }

        $this->setActionMessage($message, $failed > 0 ? 'error' : 'success');
        $this->lastDataRefreshLabel = $timestamps->formattedLabel($userId, $product);
        $this->isReportDataRefreshing = false;
        $this->refreshPendingProjects = [];
        $this->refreshProgressLabel = null;
        $this->refreshStepStartedAt = null;
        $this->refreshCurrentIndex = 0;
        $progress->markNoticed($userId, $product);
        unset($this->reportData);
    }

    private function consumeUnreadRefreshNotification(int $userId, string $product): void
    {
        if ($this->actionMessage !== null) {
            return;
        }

        $notification = Notification::query()
            ->where('user_id', $userId)
            ->where('type', IntegrationManualRefreshProgress::TYPE_REFRESHED)
            ->whereNull('read_at')
            ->orderByDesc('id')
            ->get()
            ->first(function (Notification $item) use ($product): bool {
                $payload = is_array($item->payload) ? $item->payload : [];

                return ($payload['product'] ?? null) === $product;
            });

        if ($notification === null) {
            return;
        }

        $failed = (int) (is_array($notification->payload) ? ($notification->payload['failed'] ?? 0) : 0);
        $this->setActionMessage(
            (string) $notification->text,
            $failed > 0 ? 'error' : 'success',
        );
        $notification->forceFill(['read_at' => Carbon::now()])->save();
    }

    abstract protected function reportRefreshProductKey(): string;

    abstract protected function shouldRefreshDirectBudget(): bool;
}
