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
}
