<?php

namespace App\Livewire\Channels;

use App\Enums\ChannelReportGrouping;
use App\Support\SharedProductFilters;
use Livewire\Component;

class GroupSettingsModal extends Component
{
    public ChannelReportGrouping $initialGrouping;

    public ChannelReportGrouping $grouping;

    public string $initialRoundKopecks = 'Y';

    public string $roundKopecks = 'Y';

    public function mount($initialGrouping)
    {
        $this->initialGrouping = $initialGrouping;
        $this->grouping = $initialGrouping;

        $round = SharedProductFilters::resolveRoundKopecks(true) ? 'Y' : 'N';
        $this->initialRoundKopecks = $round;
        $this->roundKopecks = $round;
    }

    public function apply(): void
    {
        $this->roundKopecks = $this->roundKopecks === 'N' ? 'N' : 'Y';
        $this->initialGrouping = $this->grouping;
        $this->initialRoundKopecks = $this->roundKopecks;

        $this->dispatch(
            'group-settings-applied',
            grouping: $this->grouping->value,
            roundKopecks: $this->roundKopecks,
        );
        $this->dispatch('modal-hide', name: 'group-settings-modal');
    }

    public function cancel(): void
    {
        $this->grouping = $this->initialGrouping;
        $this->roundKopecks = $this->initialRoundKopecks;
        $this->dispatch('modal-hide', name: 'group-settings-modal');
    }

    public function render()
    {
        return view('livewire.channels.group-settings-modal');
    }
}
