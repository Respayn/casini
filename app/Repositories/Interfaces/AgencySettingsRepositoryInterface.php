<?php

namespace App\Repositories\Interfaces;

use App\Data\AgencyData;
use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;

interface AgencySettingsRepositoryInterface extends RepositoryInterface
{
    public function getAgency(int $id): AgencyData;
    public function saveAgency(AgencyData|AgencySettingsForm $data): void;
    public function createAgency(AgencyData|AgencySettingsForm $data): AgencyData;

}
