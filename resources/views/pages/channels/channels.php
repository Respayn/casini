<?php

namespace App\Livewire\Channels;

use App\Contracts\ChannelReportServiceInterface;
use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportColumnData;
use App\Data\TableReportData;
use App\Enums\ChannelReportGrouping;
use App\Livewire\Concerns\WithReportDataRefresh;
use App\Livewire\Concerns\WithSidebarProjectFilter;
use App\Support\SharedProductFilters;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Каналы')]
class extends Component
{
    use WithReportDataRefresh;
    use WithSidebarProjectFilter;

    public ChannelReportQueryData $queryData;

    /**
     * Сохраненные настройки для отмены изменений в модальных окнах
     */
    public ?ChannelReportQueryData $originalQueryData = null;

    public ?string $actionMessage = null;

    public string $actionMessageType = 'success';

    private ChannelReportServiceInterface $channelReportService;

    public function boot(ChannelReportServiceInterface $channelReportService)
    {
        $this->channelReportService = $channelReportService;
    }

    public function mount()
    {
        $this->queryData = $this->channelReportService->getUserSettings(
            Auth::user()->id,
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

    public function updatedQueryDataDateFrom(): void
    {
        if ($this->restoreLockedRefreshSliceFilters()) {
            return;
        }

        $this->queryData->clampPeriodToPresent();
        unset($this->reportData);
    }

    public function updatedQueryDataDateTo(): void
    {
        if ($this->restoreLockedRefreshSliceFilters()) {
            return;
        }

        $this->queryData->clampPeriodToPresent();
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

    /**
     * Применяет изменения в настройках столбцов
     */
    public function applySettingsSnapshot()
    {
        $pinnedField = $this->pinnedGroupingColumnField();
        if ($pinnedField !== null) {
            $this->queryData->columns->each(function (TableReportColumnData $col) use ($pinnedField): void {
                if ($col->field === $pinnedField) {
                    $col->isVisible = true;
                }
            });
        }

        $this->originalQueryData = null;
        $this->persistUserSettings();
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

        // При закреплённой колонке двигаем только остальные, её order не трогаем.
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
    public function reportData(): TableReportData
    {
        $this->persistUserSettings();

        $query = clone $this->queryData;
        if ($this->sidebarProjectId !== null) {
            $query->grouping = ChannelReportGrouping::NONE;
        }

        return $this->channelReportService->getReportData($query, $this->sidebarProjectId);
    }

    #[On('group-settings-applied')]
    public function applyGrouping($grouping)
    {
        SharedProductFilters::rememberGrouping((string) $grouping);
        $this->queryData->grouping = ChannelReportGrouping::from(
            SharedProductFilters::normalizeGrouping((string) $grouping),
        );
        $this->persistUserSettings();
        unset($this->reportData, $this->visibleColumns);
    }

    private function persistUserSettings(): void
    {
        $this->channelReportService->saveUserSettings(
            Auth::user()->id,
            $this->queryData,
        );
    }

    protected function reportRefreshProductKey(): string
    {
        return 'channels';
    }

    protected function shouldRefreshDirectBudget(): bool
    {
        return true;
    }

    protected function setActionMessage(string $message, string $type): void
    {
        $this->actionMessage = $message;
        $this->actionMessageType = $type;
    }
};
