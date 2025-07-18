<?php

namespace App\Livewire\SystemSettings\Agency;

use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AgencySwitcherComponent extends Component
{
    public array $agencies = [];
    public $selectedAgencyId = null;
    public $isSuperAdmin = false;

    protected $listeners = ['agenciesUpdated' => 'refreshAgencies', 'agencyCreated' => 'refreshAgencies', 'createIfNotSelected' => 'createIfNotSelected'];

    public function mount()
    {
        $this->refreshAgencies();

        $user = auth()->user();
        $this->agencies = $user->agencies()->get()->map(function ($agency) {
            return [
                'id' => $agency->id,
                'name' => $agency->name,
            ];
        })->toArray();

        $this->selectedAgencyId = session('current_agency_id')
            ?? ($this->agencies[0]['id'] ?? null);

        session(['current_agency_id' => $this->selectedAgencyId]);
    }

    public function refreshAgencies($newAgencyId = null)
    {
        $user = auth()->user();
        $this->agencies = $user->agencies()->get()->map(function ($agency) {
            return [
                'id' => $agency->id,
                'name' => $agency->name,
            ];
        })->toArray();
        $this->isSuperAdmin = $user->hasRole('super-admin');

        if ($newAgencyId) {
            $this->changeAgency($newAgencyId);
        } elseif (!$this->selectedAgencyId && isset($this->agencies[0]['id'])) {
            $this->selectedAgencyId = $this->agencies[0]['id'];
        }
    }

    public function createIfNotSelected()
    {
        if (empty($this->selectedAgencyId)) {
            return $this->dispatch('modal-show', name: 'agency-modal');
        }

        return redirect()->route('system-settings.agency');
    }

    public function updatedSelectedAgencyId($value)
    {
        session(['current_agency_id' => $value]);
        $this->dispatch('agenciesUpdated');
        return redirect(request()->header('referer'));
    }

    public function changeAgency($value)
    {
        $this->selectedAgencyId = $value;
        $this->updatedSelectedAgencyId($value);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('livewire.system-settings.agency.switcher');
    }
}
