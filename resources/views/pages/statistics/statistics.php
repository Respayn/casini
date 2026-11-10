<?php

namespace App\Livewire\Statistics;

use App\Data\Statistics\StatisticsReportQueryData;
use App\Data\TableReportColumnData;
use App\Data\TableReportData;
use App\Livewire\Concerns\WithReportDataRefresh;
use App\Livewire\Concerns\WithSidebarProjectFilter;
use App\Services\Statistics\StatisticsService;
use App\Support\SharedProductFilters;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Статистика - Casini')]
class extends Component
{
    use WithReportDataRefresh;
    use WithSidebarProjectFilter;

    public StatisticsReportQueryData $queryData;

    /**
     * Сохраненные настройки для отмены изменений в модальных окнах
     */
    public ?StatisticsReportQueryData $originalQueryData = null;

    public ?string $actionMessage = null;

    public string $actionMessageType = 'success';

    private StatisticsService $statisticsService;

    public function boot(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    public function mount()
    {
        $this->queryData = $this->statisticsService->getUserSettings(
            Auth::user()->id,
            session('current_agency_id') ? (int) session('current_agency_id') : null,
        );
        $this->queryData->clampPeriodToPresent();
        $this->applySharedProductFilters();
    }

    public function updatedQueryDataShowInactive(): void
    {
        SharedProductFilters::rememberShowInactive($this->queryData->showInactive);
        unset($this->reportData);
    }

    public function updatedQueryDataIncludeVat(): void
    {
        SharedProductFilters::rememberIncludeVat($this->queryData->includeVat);
        unset($this->reportData);
    }

    private function applySharedProductFilters(): void
    {
        $this->queryData->showInactive = SharedProductFilters::resolveShowInactive($this->queryData->showInactive);
        $this->queryData->includeVat = SharedProductFilters::resolveIncludeVat($this->queryData->includeVat);
    }

    protected function afterSidebarProjectFilterChanged(): void
    {
        unset($this->reportData);
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

    /**
     * Применяет изменения в настройках столбцов / отчёта
     */
    public function applySettingsSnapshot(): void
    {
        if ($this->originalQueryData !== null
            && $this->queryData->detailLevel !== $this->originalQueryData->detailLevel) {
            $this->rebuildQueryDataForPeriod();
        }

        $this->originalQueryData = null;
        unset($this->reportData);
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

        return $this->statisticsService->getReportData($this->queryData, $this->sidebarProjectId);
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
        $rebuilt->treatMissingPlanAsUnmet = $previous->treatMissingPlanAsUnmet;
        $rebuilt->applySavedColumnPreferences($previous->columns);

        $this->queryData = $rebuilt;
        unset($this->reportData);
    }

    protected function reportRefreshProductKey(): string
    {
        return 'statistics';
    }

    protected function shouldRefreshDirectBudget(): bool
    {
        return false;
    }

    /**
     * @param  list<int>  $projectIds
     */
    protected function afterSuccessfulReportDataRefresh(array $projectIds): void
    {
        if (! $this->queryData->isSingleMonthPeriod()) {
            return;
        }

        $this->statisticsService->recalculateMonthlyBonuses(
            $projectIds,
            $this->queryData->dateFrom->copy()->startOfMonth()->startOfDay(),
            $this->queryData->includeVat,
        );
    }

    protected function setActionMessage(string $message, string $type): void
    {
        $this->actionMessage = $message;
        $this->actionMessageType = $type;
    }
};
