<?php

namespace App\Livewire\Channels;

use App\Contracts\ChannelReportServiceInterface;
use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportColumnData;
use App\Data\TableReportData;
use App\Enums\ChannelReportGrouping;
use App\Livewire\Concerns\WithReportDataRefresh;
use App\Livewire\Concerns\WithSidebarProjectFilter;
use App\Services\GoogleSheetsService;
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
    }

    protected function afterSidebarProjectFilterChanged(): void
    {
        unset($this->reportData);
    }

    public function updatedQueryDataDateFrom(): void
    {
        $this->queryData->clampPeriodToPresent();
        unset($this->reportData);
    }

    public function updatedQueryDataDateTo(): void
    {
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
        $this->originalQueryData = null;
        $this->persistUserSettings();
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
    public function reportData(): TableReportData
    {
        $this->persistUserSettings();

        return $this->channelReportService->getReportData($this->queryData, $this->sidebarProjectId);
    }

    #[On('group-settings-applied')]
    public function applyGrouping($grouping)
    {
        $this->queryData->grouping = ChannelReportGrouping::from($grouping);
        $this->persistUserSettings();
        unset($this->reportData);
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

    protected function afterSuccessfulReportDataRefresh(array $projectIds): void
    {
        if ($projectIds === []) {
            return;
        }

        app(GoogleSheetsService::class)->syncProjects(
            $projectIds,
            $this->queryData->dateTo->copy()->startOfMonth(),
            manual: true,
        );

        unset($this->reportData);
    }
};
