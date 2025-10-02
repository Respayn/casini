<?php

namespace App\Livewire\Channels;

use App\Enums\ChannelReportGrouping;
use Livewire\Component;

class GroupSettingsModal extends Component
{
    public ChannelReportGrouping $initialGrouping;
    public ChannelReportGrouping $grouping;

    public function mount($initialGrouping)
    {
        $this->initialGrouping = $initialGrouping;
        $this->grouping = $initialGrouping;
    }

    public function setGrouping($grouping)
    {
        $this->grouping = ChannelReportGrouping::from($grouping);
    }

    public function render()
    {
        return view('livewire.channels.group-settings-modal');
    }
}
