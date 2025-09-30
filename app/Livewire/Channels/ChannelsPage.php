<?php

namespace App\Livewire\Channels;

use App\Contracts\ChannelReportServiceInterface;
use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportColumnData;
use App\Data\TableReportData;
use App\Enums\ChannelReportGrouping;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

#[Title('Каналы')]
class ChannelsPage extends Component
{
    public ChannelReportQueryData $queryData;

    public array $selectedProjects = [];
    public array $selectedGroups = [];

    public bool $selectAll = false;

    /**
     * Выбранное действие для массовых операций
     * TODO: перевести на Backed Enum
     * @var string
     */
    public string $bulkAction = '';

    private ChannelReportServiceInterface $channelReportService;

    public function boot(ChannelReportServiceInterface $channelReportService)
    {
        $this->channelReportService = $channelReportService;
    }

    public function mount()
    {
        $this->queryData = ChannelReportQueryData::create();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedProjects = $this->reportData->groups->flatMap(function ($group) {
                return $group->rows->pluck('id');
            })->toArray();

            $this->selectedGroups = $this->reportData->groups->keys()->toArray();
        } else {
            $this->selectedProjects = [];
            $this->selectedGroups = [];
        }
    }

    public function updatedSelectedGroups($value, $key)
    {
        if ($key !== null) {
            $group = $this->reportData->groups->get($key);

            if ($group) {
                $projectIds = $group->rows->pluck('id')->toArray();

                if (in_array($key, $this->selectedGroups)) {
                    $this->selectedProjects = array_unique(array_merge($this->selectedProjects, $projectIds));
                } else {
                    $this->selectedProjects = array_diff($this->selectedProjects, $projectIds);
                }
            }
        }

        $this->checkSelectAll();
    }

    public function updatedSelectedProjects($value, $key)
    {
        if ($key !== null) {
            $this->updateGroupCheckboxes();
            $this->checkSelectAll();
        }
    }

    private function updateGroupCheckboxes()
    {
        $newSelectedGroups = [];

        foreach ($this->reportData->groups as $groupIndex => $group) {
            $projectIds = $group->rows->pluck('id')->toArray();

            if (!empty($projectIds) && count(array_intersect($projectIds, $this->selectedProjects)) === count($projectIds)) {
                $newSelectedGroups[] = $groupIndex;
            }
        }

        $this->selectedGroups = $newSelectedGroups;
    }

    private function checkSelectAll()
    {
        $allProjectIds = $this->reportData->groups->flatMap(function ($group) {
            return $group->rows->pluck('id');
        })->toArray();

        // Если все проекты выбраны, то selectAll = true
        $this->selectAll = !empty($allProjectIds) &&
            count($this->selectedProjects) === count($allProjectIds) &&
            empty(array_diff($allProjectIds, $this->selectedProjects));
    }

    #[Renderless]
    public function sortColumn($item, $position)
    {
        $column = $this->queryData->columns->first(fn($v) => $v->field === $item);
        $oldPosition = $column->order;

        if ($oldPosition === $position) {
            return;
        }

        $this->queryData->columns->each(function ($col) use ($oldPosition, $position) {
            if ($col->order == $oldPosition) {
                $col->order = $position;
            } elseif ($oldPosition < $position && $col->order > $oldPosition && $col->order <= $position) {
                $col->order--;
            } elseif ($oldPosition > $position && $col->order >= $position && $col->order < $oldPosition) {
                $col->order++;
            }
        });

        $this->queryData->columns = $this->queryData->columns->sortBy(fn(TableReportColumnData $col) => $col->order);
    }

    #[Computed]
    public function visibleColumns()
    {
        return $this->queryData->columns->filter(function (TableReportColumnData $col, $key) {
            return $col->isVisible;
        });
    }

    #[Computed]
    public function reportData(): TableReportData
    {
        return $this->channelReportService->getReportData($this->queryData);
    }

    public function applyGrouping($grouping)
    {
        $this->queryData->grouping = ChannelReportGrouping::from($grouping);
        $this->selectedProjects = [];
        $this->selectedGroups = [];
        $this->selectAll = false;
    }

    public function makeBulkAction()
    {
        if ($this->bulkAction === '') {
            Toaster::error('Выберите действие!');
        } else {
            Toaster::success('Действие выполнено!');
        }
    }

    public function render()
    {
        return view('livewire.channels.channels-page', [
            'reportData' => $this->reportData,
            'hasNoProjects' => $this->reportData->groups->isEmpty()
        ]);
    }
}
