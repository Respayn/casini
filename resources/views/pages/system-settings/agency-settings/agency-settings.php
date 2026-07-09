<?php

namespace App\Livewire\SystemSettings\Agency;

use App\Data\AgencyData;
use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use App\Services\AgencySettingsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts::system-settings')]
class extends Component
{
    use WithFileUploads;

    public AgencySettingsForm $form;

    public ?int $agency = null;

    public function mount(AgencySettingsService $service)
    {
        $agencyId = $this->agency ?? (int)session('current_agency_id');

        if (!$agencyId) {
            abort(404, 'Агентство не найдено');
        }

        // TODO: Проверка доступа

        // Получаем настройки агентства
        $agency = $service->getAgency($agencyId);
        $this->form->from($agency);
    }

    public function save(AgencySettingsService $service)
    {
        $this->validate();

        // Сохраняем логотип, если был загружен
        if ($this->form->logo) {
            $path = $this->form->logo->store('agency_logos', 'public');
            $this->form->logoSrc = $path;
        }

        $service->saveAgency($this->form);

        $this->dispatch('agenciesUpdated');

        session()->flash('success', 'Настройки успешно сохранены.');
    }

    public function deleteLogo()
    {
        $this->form->logo = null;
        $this->form->logoSrc = null;
    }

    #[Computed]
    public function timezones()
    {
        return \DateTimeZone::listIdentifiers();
    }
};