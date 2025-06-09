<?php

namespace App\Livewire\SystemSettings\Agency;

use App\Data\AgencyData;
use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use App\Services\AgencySettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.system-settings')]
class AgencySettingsComponent extends Component
{
    use WithFileUploads;

    public AgencySettingsForm $form;

    public ?int $agency = null;

    public function mount(AgencySettingsService $service)
    {
        $agencyId = $this->agency;
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

        $agencyDto = AgencyData::from([
            'id' => $this->form->id,
            'name' => $this->form->name,
            'timeZone' => $this->form->timeZone,
            'url' => $this->form->url,
            'email' => $this->form->email,
            'phone' => $this->form->phone,
            'address' => $this->form->address,
            'logoSrc' => $this->form->logoSrc,
            'admins' => $this->form->admins ?? [],
        ]);

        $service->saveAgency($agencyDto);

        $this->dispatch('agenciesUpdated');

        session()->flash('success', 'Настройки успешно сохранены.');
    }

    public function deleteLogo()
    {
        if ($this->form->logoSrc) {
            \Storage::disk('public')->delete($this->form->logoSrc);
            $this->form->logoSrc = null;
        }
    }

    public function render()
    {
        return view('livewire.agency.settings-form', [
            'form' => $this->form,
            'timezones' => \DateTimeZone::listIdentifiers(),
            'admins' => $this->form->admins,
        ]);
    }
}
