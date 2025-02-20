<?php

namespace App\Livewire\SystemSettings\Dictionaries;

use App\Services\TooltipService;
use Illuminate\Support\Collection;
use Livewire\Component;

class TooltipDictionary extends Component
{
    private TooltipService $tooltipService;

    public Collection $tooltips;

    public function mount()
    {
        $this->tooltips = $this->tooltipService->getTooltips();
    }

    public function boot(TooltipService $tooltipService)
    {
        $this->tooltipService = $tooltipService;
    }

    public function updateTooltip(string $code, string $content)
    {
        $this->tooltipService->updateTooltip($code, $content);
    }

    public function render()
    {
        return view('livewire.system-settings.dictionaries.tooltip-dictionary');
    }
}
