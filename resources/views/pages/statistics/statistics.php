<?php

namespace App\Livewire\Statistics;

use App\Data\Statistics\StatisticsReportQueryData;
use App\Data\TableReportColumnData;
use App\Data\TableReportData;
use App\Enums\ChannelReportGrouping;
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
#[Title('Статистика')]
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

    public string $roundKopecks = 'Y';

    public ?string $originalRoundKopecks = null;

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
        $this->roundKopecks = SharedProductFilters::resolveRoundKopecks(true) ? 'Y' : 'N';
    }

    public function updatedQueryDataShowInactive(): void
    {
        SharedProductFilters::rememberShowInactive($this->queryData->showInactive);
        unset($this->reportData);
    }

    public function updatedQueryDataIncludeVat(): void
    {
        if ($this->restoreLockedRefreshSliceFilters()) {
            return;
        }

        SharedProductFilters::rememberIncludeVat($this->queryData->includeVat);
        unset($this->reportData);
    }

    private function applySharedProductFilters(): void
    {
        $this->queryData->showInactive = SharedProductFilters::resolveShowInactive($this->queryData->showInactive);
        $this->queryData->includeVat = SharedProductFilters::resolveIncludeVat($this->queryData->includeVat);
        $this->queryData->grouping = ChannelReportGrouping::from(
            SharedProductFilters::resolveGrouping($this->queryData->grouping->value),
        );
    }

    public function pinnedGroupingColumnField(): ?string
    {
        return SharedProductFilters::pinnedColumnField($this->queryData->grouping->value);
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
        $this->originalRoundKopecks = $this->roundKopecks;
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

        if ($this->originalRoundKopecks !== null) {
            $this->roundKopecks = $this->originalRoundKopecks;
            $this->originalRoundKopecks = null;
        }
    }

    public function updatedQueryDataDateFrom(): void
    {
        if ($this->restoreLockedRefreshSliceFilters()) {
            return;
        }

        $this->rebuildQueryDataForPeriod();
    }

    public function updatedQueryDataDateTo(): void
    {
        if ($this->restoreLockedRefreshSliceFilters()) {
            return;
        }

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

        SharedProductFilters::rememberGrouping($this->queryData->grouping->value);
        $this->queryData->grouping = ChannelReportGrouping::from(
            SharedProductFilters::normalizeGrouping($this->queryData->grouping->value),
        );

        $this->roundKopecks = $this->roundKopecks === 'N' ? 'N' : 'Y';
        SharedProductFilters::rememberRoundKopecks($this->roundKopecks !== 'N');

        $pinnedField = $this->pinnedGroupingColumnField();
        if ($pinnedField !== null) {
            $this->queryData->columns->each(function (TableReportColumnData $col) use ($pinnedField): void {
                if ($col->field === $pinnedField) {
                    $col->isVisible = true;
                }
            });
        }

        $this->originalQueryData = null;
        $this->originalRoundKopecks = null;
        unset($this->reportData, $this->visibleColumns);
    }

    #[Renderless]
    public function sortColumn($item, $position)
    {
        $pinnedField = $this->pinnedGroupingColumnField();
        if ($pinnedField !== null && $item === $pinnedField) {
            return;
        }

        if ($pinnedField === null) {
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

            return;
        }

        $movable = $this->queryData->columns
            ->filter(fn (TableReportColumnData $col) => $col->field !== $pinnedField)
            ->sortBy(fn (TableReportColumnData $col) => $col->order)
            ->values();

        $fromIndex = $movable->search(
            fn (TableReportColumnData $col) => $col->field === $item,
        );
        if ($fromIndex === false) {
            return;
        }

        $toIndex = max(0, min((int) $position, $movable->count() - 1));
        if ($fromIndex === $toIndex) {
            return;
        }

        $slots = $movable->pluck('order')->sort()->values();
        $moved = $movable->pull($fromIndex);
        $movable = $movable->values();
        $movable->splice($toIndex, 0, [$moved]);

        foreach ($movable->values() as $index => $col) {
            $col->order = $slots[$index];
        }

        $this->queryData->columns = $this->queryData->columns->sortBy(
            fn (TableReportColumnData $col) => $col->order,
        );
    }

    #[Computed]
    public function visibleColumns()
    {
        $columns = $this->queryData->columns
            ->filter(fn (TableReportColumnData $col) => $col->isVisible)
            ->sortBy(fn (TableReportColumnData $col) => $col->order)
            ->values();

        $pinnedField = $this->pinnedGroupingColumnField();
        if ($pinnedField === null) {
            return $columns;
        }

        $pinned = $this->queryData->columns->first(
            fn (TableReportColumnData $col) => $col->field === $pinnedField,
        );

        if ($pinned === null) {
            return $columns;
        }

        $pinned->isVisible = true;

        return $columns
            ->reject(fn (TableReportColumnData $col) => $col->field === $pinnedField)
            ->prepend($pinned)
            ->values();
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

        $query = clone $this->queryData;
        if ($this->sidebarProjectId !== null) {
            $query->grouping = ChannelReportGrouping::NONE;
        }

        return $this->statisticsService->getReportData($query, $this->sidebarProjectId);
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
