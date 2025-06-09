<?php

namespace App\Livewire\SystemSettings\Agency;

use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use App\Services\AgencySettingsService;
use Livewire\Component;

class CreateAgencyForm extends Component
{
    public AgencySettingsForm $form;
    protected AgencySettingsService $agencyService;

    public function boot(AgencySettingsService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    public function mount()
    {
        // Для создания агентства всегда пусто
        $this->form->admins = [];
        // Остальные поля по умолчанию
    }

    public function submit()
    {
        $this->form->validate();

        $agency = $this->agencyService->createAgency($this->form);

        $this->dispatch('modal-hide', name: 'agency-modal');
        $this->dispatch('agencyCreated', $agency->id);
        session()->flash('success', 'Агентство создано!');
    }

    public function render()
    {
        return view('livewire.system-settings.agency.create-agency-form');
    }
}
