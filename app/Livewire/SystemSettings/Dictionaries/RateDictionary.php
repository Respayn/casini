<?php

namespace App\Livewire\SystemSettings\Dictionaries;

use App\Livewire\Forms\SystemSettings\Dictionaries\CreateRateForm;
use App\Services\RateService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RateDictionary extends Component
{
    private RateService $rateService;

    public CreateRateForm $form;
    
    public Collection $historyExpandedStates;

    public int $selectedRateId = 0;

    public function mount()
    {
        $this->historyExpandedStates = collect();
    }

    public function boot(RateService $rateService)
    {
        $this->rateService = $rateService;
    }

    public function store()
    {
        $this->validate();

        if ($this->selectedRateId !== 0) {
            $this->rateService->updateRate($this->form, $this->pull('selectedRateId'));
        } else {
            $this->rateService->createRate($this->form);
        }

        $this->form->reset();
        $this->dispatch('modal-hide', name: 'rate-dictionary-add-modal');
    }

    public function delete()
    {
        $this->rateService->deleteRate($this->pull('selectedRateId'));
        $this->dispatch('modal-hide', name: 'rate-dictionary-delete-modal');
    }

    #[Computed()]
    public function rates()
    {
        return $this->rateService->getRates();
    }

    public function render()
    {
        return view('livewire.system-settings.dictionaries.rate-dictionary');
    }
}
