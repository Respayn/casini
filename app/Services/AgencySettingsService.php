<?php

namespace App\Services;

use App\Data\AgencyData;
use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use App\Repositories\Interfaces\AgencySettingsRepositoryInterface;

class AgencySettingsService
{
    public function __construct(
        protected AgencySettingsRepositoryInterface $repository
    ) {}

    public function getAgency(int $id): AgencyData
    {
        return $this->repository->getAgency($id);
    }

    public function saveAgency(AgencySettingsForm|AgencyData $data): void
    {
        $this->repository->saveAgency($data);
    }

    public function createAgency(AgencySettingsForm|AgencyData $form): AgencyData
    {
        return $this->repository->createAgency($form);
    }

    public function getActualAgencyId()
    {
        $user = auth()->user();

        $agencies = $user->agencies()->get()->map(function ($agency) {
            return [
                'id' => $agency->id,
                'name' => $agency->name,
            ];
        })->toArray();

        $selectedAgencyId = session('current_agency_id')
            ?? ($agencies[0]['id'] ?? null);

        session(['current_agency_id' => (int)$selectedAgencyId ?? null]);

        return $selectedAgencyId;
    }

    public function getActualAgencyIdWithList(): array
    {
        $user = auth()->user();

        $agencies = $user->agencies()->get()->map(function ($agency) {
            return [
                'id' => $agency->id,
                'name' => $agency->name,
            ];
        })->toArray();

        $selectedAgencyId = session('current_agency_id')
            ?? ($agencies[0]['id'] ?? null);

        session(['current_agency_id' => (int)$selectedAgencyId ?? null]);

        return [$selectedAgencyId, array_slice($agencies, 0, 1)];
    }
}
