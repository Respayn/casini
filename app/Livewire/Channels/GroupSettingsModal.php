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

    public function apply(): void
    {
        $this->initialGrouping = $this->grouping;
        $this->dispatch('group-settings-applied', grouping: $this->grouping->value);
        $this->dispatch('modal-hide', name: 'group-settings-modal');
    }

    public function cancel(): void
    {
        $this->grouping = $this->initialGrouping;
        $this->dispatch('modal-hide', name: 'group-settings-modal');
    }

    public function render()
    {
        return view('livewire.channels.group-settings-modal');
    }
}
