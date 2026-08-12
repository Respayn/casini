<?php

namespace App\Livewire\SystemSettings\Agency;

use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use App\Services\AgencySettingsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts::system-settings')]
#[Title('Настройка агентства')]
class extends Component
{
    use WithFileUploads;

    public AgencySettingsForm $form;

    public ?int $agency = null;

    /** Показать кнопки Сохранить/Отменить сразу после загрузки (обычно false) */
    public bool $startWithPendingChanges = false;

    /** Показать баннер «Изменения сохранены» после редиректа с успешного save */
    public bool $startWithSuccessMessage = false;

    public function mount(AgencySettingsService $service)
    {
        $agencyId = $this->agency ?? (int) session('current_agency_id');

        if (! $agencyId) {
            abort(404, 'Агентство не найдено');
        }

        // TODO: Проверка доступа

        $agency = $service->getAgency($agencyId);
        $this->form->from($agency);

        if (session()->pull('agency_settings_saved')) {
            $this->startWithSuccessMessage = true;
        }
    }

    public function save(AgencySettingsService $service)
    {
        $this->validate();

        if ($this->form->logo) {
            $path = $this->form->logo->store('agency_logos', 'public');
            $this->form->logoSrc = $path;
        }

        $service->saveAgency($this->form);

        $this->dispatch('agenciesUpdated');

        session()->flash('agency_settings_saved', true);

        return $this->redirect(
            route('system-settings.agency', ['agency' => $this->form->id]),
            navigate: true
        );
    }

    public function cancelChanges(): mixed
    {
        return $this->redirect(
            route('system-settings.agency', ['agency' => $this->form->id]),
            navigate: true
        );
    }

    public function deleteLogo()
    {
        $this->form->logo = null;
        $this->form->logoSrc = null;
        $this->dispatch('agency-settings-mark-dirty');
    }

    #[Computed]
    public function canSubmitAgencySettings(): bool
    {
        return filled($this->form->name)
            && filled($this->form->timeZone)
            && filled($this->form->directBudgetRefreshTime);
    }

    #[Computed]
    public function timezones()
    {
        return \DateTimeZone::listIdentifiers();
    }
};
