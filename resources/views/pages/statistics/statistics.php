<?php

namespace App\Livewire\Statistics;

use App\Data\Statistics\StatisticsReportQueryData;
use App\Data\TableReportColumnData;
use App\Data\TableReportData;
use App\Domain\Statistics\Services\StatisticsService;
use App\Enums\ChannelBulkAction;
use App\Services\Channels\ChannelDirectMetricsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Статистика - Casini')]
class extends Component
{
    public StatisticsReportQueryData $queryData;

    /**
     * Сохраненные настройки для отмены изменений в модальных окнах
     */
    public ?StatisticsReportQueryData $originalQueryData = null;

    public array $selectedProjects = [];

    public array $selectedGroups = [];

    public bool $selectAll = false;

    public string $bulkAction = '';

    public ?string $actionMessage = null;

    public string $actionMessageType = 'success';

    private StatisticsService $statisticsService;

    private ChannelDirectMetricsService $directMetricsService;

    public function boot(
        StatisticsService $statisticsService,
        ChannelDirectMetricsService $directMetricsService,
    ) {
        $this->statisticsService = $statisticsService;
        $this->directMetricsService = $directMetricsService;
    }

    public function mount()
    {
        $this->queryData = $this->statisticsService->getUserSettings(
            Auth::user()->id,
        );
        $this->queryData->clampPeriodToPresent();
    }

    /**
     * Сохраняет текущие настройки для возможности отмены
     */
    #[Renderless]
    public function saveSettingsSnapshot()
    {
        $this->originalQueryData = clone $this->queryData;
    }

    /**
     * Отменяет изменения в настройках столбцов
     */
    public function dropSettingsSnapshot()
    {
        if ($this->originalQueryData) {
            $this->queryData = clone $this->originalQueryData;
            $this->originalQueryData = null;
        }
    }

    public function updatedQueryDataDateFrom(): void
    {
        $this->rebuildQueryDataForPeriod();
    }

    public function updatedQueryDataDateTo(): void
    {
        $this->rebuildQueryDataForPeriod();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedProjects = $this->reportData->groups
                ->flatMap(fn ($group) => $group->rows->pluck('id'))
                ->toArray();

            $this->selectedGroups = $this->reportData->groups
                ->keys()
                ->toArray();
        } else {
            $this->selectedProjects = [];
            $this->selectedGroups = [];
        }
    }

    public function updatedSelectedGroups($value, $key): void
    {
        if ($key === null) {
            return;
        }

        $group = $this->reportData->groups->get($key);

        if ($group === null) {
            return;
        }

        $projectIds = $group->rows->pluck('id')->toArray();

        if (in_array($key, $this->selectedGroups, true)) {
            $this->selectedProjects = array_values(array_unique(
                array_merge($this->selectedProjects, $projectIds),
            ));
        } else {
            $this->selectedProjects = array_values(array_diff(
                $this->selectedProjects,
                $projectIds,
            ));
        }

        $this->checkSelectAll();
    }

    public function updatedSelectedProjects($value, $key): void
    {
        if ($key !== null) {
            $this->updateGroupCheckboxes();
            $this->checkSelectAll();
        }
    }

    /**
     * Применяет изменения в настройках столбцов
     */
    public function applySettingsSnapshot()
    {
        if ($this->originalQueryData !== null
            && $this->queryData->detailLevel !== $this->originalQueryData->detailLevel) {
            $this->rebuildQueryDataForPeriod();
        }

        if ($this->originalQueryData !== null
            && $this->queryData->grouping !== $this->originalQueryData->grouping) {
            $this->clearProjectSelection();
            unset($this->reportData);
        }

        $this->originalQueryData = null;
    }

    public function makeBulkAction(): void
    {
        $action = ChannelBulkAction::tryFrom($this->bulkAction);

        if ($action === null) {
            $this->setActionMessage('Выберите массовое действие', 'error');

            return;
        }

        if ($this->selectedProjects === []) {
            $this->setActionMessage('Выберите клиенто-проекты', 'error');

            return;
        }

        $stats = match ($action) {
            ChannelBulkAction::RefreshBudgetRemains => $this->directMetricsService->refreshBudgets(
                $this->selectedProjects,
            ),
            ChannelBulkAction::RefreshSpendings => $this->directMetricsService->refreshSpendingsForProjects(
                $this->selectedProjects,
                $this->queryData->dateFrom,
                $this->queryData->dateTo,
                $this->queryData->includeVat,
            ),
        };

        unset($this->reportData);

        if (! empty($stats['error'])) {
            $this->setActionMessage($stats['error'], 'error');

            return;
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

    #[Renderless]
    public function sortColumn($item, $position)
    {
        $column = $this->queryData->columns->first(
            fn ($v) => $v->field === $item,
        );
        $oldPosition = $column->order;

        if ($oldPosition === $position) {
            return;
        }

        $this->queryData->columns->each(function ($col) use (
            $oldPosition,
            $position,
        ) {
            if ($col->order == $oldPosition) {
                $col->order = $position;
            } elseif (
                $oldPosition < $position &&
                $col->order > $oldPosition &&
                $col->order <= $position
            ) {
                $col->order--;
            } elseif (
                $oldPosition > $position &&
                $col->order >= $position &&
                $col->order < $oldPosition
            ) {
                $col->order++;
            }
        });

        $this->queryData->columns = $this->queryData->columns->sortBy(
            fn (TableReportColumnData $col) => $col->order,
        );
    }

    #[Computed]
    public function visibleColumns()
    {
        return $this->queryData->columns->filter(function (
            TableReportColumnData $col,
            $key,
        ) {
            return $col->isVisible;
        });
    }

    #[Computed]
    public function sortableColumns()
    {
        return $this->queryData->columns->filter(function (TableReportColumnData $col, $key) {
            return $col->isSortable;
        });
    }

    #[Computed]
    public function reportData(): TableReportData
    {
        // Как в Каналах: сохраняем настройки пользователя при построении отчёта
        $this->statisticsService->saveUserSettings(
            Auth::user()->id,
            $this->queryData,
        );

        return $this->statisticsService->getReportData($this->queryData);
    }

    private function rebuildQueryDataForPeriod(): void
    {
        $this->queryData->clampPeriodToPresent();

        $previous = $this->queryData;
        $rebuilt = StatisticsReportQueryData::create(
            $previous->detailLevel,
            $previous->dateFrom,
            $previous->dateTo,
        );
        $rebuilt->grouping = $previous->grouping;
        $rebuilt->showInactive = $previous->showInactive;
        $rebuilt->includeVat = $previous->includeVat;
        $rebuilt->accumulateData = $previous->accumulateData;
        $rebuilt->highlightUnmetKpi = $previous->highlightUnmetKpi;
        $rebuilt->applySavedColumnPreferences($previous->columns);

        $this->queryData = $rebuilt;
        $this->clearProjectSelection();
        unset($this->reportData);
    }

    private function updateGroupCheckboxes(): void
    {
        $newSelectedGroups = [];

        foreach ($this->reportData->groups as $groupIndex => $group) {
            $projectIds = $group->rows->pluck('id')->toArray();

            if (
                $projectIds !== []
                && count(array_intersect($projectIds, $this->selectedProjects)) === count($projectIds)
            ) {
                $newSelectedGroups[] = $groupIndex;
            }
        }

        $this->selectedGroups = $newSelectedGroups;
    }

    private function checkSelectAll(): void
    {
        $allProjectIds = $this->reportData->groups
            ->flatMap(fn ($group) => $group->rows->pluck('id'))
            ->toArray();

        $this->selectAll = $allProjectIds !== []
            && count($this->selectedProjects) === count($allProjectIds)
            && array_diff($allProjectIds, $this->selectedProjects) === [];
    }

    private function clearProjectSelection(): void
    {
        $this->selectedProjects = [];
        $this->selectedGroups = [];
        $this->selectAll = false;
    }

    private function setActionMessage(string $message, string $type): void
    {
        $this->actionMessage = $message;
        $this->actionMessageType = $type;
    }
};
