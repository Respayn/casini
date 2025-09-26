<?php

namespace App\Livewire\Channels;

use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportColumnData;
use App\Data\TableReportData;
use App\Services\ChannelReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Каналы')]
class ChannelsPage extends Component
{
    public ChannelReportQueryData $queryData;

    private ChannelReportService $channelReportService;

    public function boot(ChannelReportService $channelReportService)
    {
        $this->channelReportService = $channelReportService;
    }

    public function mount()
    {
        $this->queryData = ChannelReportQueryData::create();
    }

    public function getReportData(): TableReportData
    {
        return $this->channelReportService->getReportData($this->queryData);
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

    public function render()
    {
        $reportData = $this->getReportData();
        $hasNoProjects = $reportData->groups->isEmpty();

        return view('livewire.channels.channels-page', compact([
            'reportData',
            'hasNoProjects'
        ]));
    }
}
