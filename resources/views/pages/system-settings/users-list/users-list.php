<?php

namespace App\Livewire\Users;

use App\Services\AgencySettingsService;
use App\Services\UserService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::system-settings')]
class extends Component
{
    public bool $onlyActive = false;
    public array $users = [];
    public ?int $agencyId = null;

    public function mount(UserService $userService, AgencySettingsService $agencySettingsService)
    {
        $this->agencyId = $agencySettingsService->getActualAgencyId();
        $this->loadUsers($userService);
    }

    public function updatedOnlyActive(UserService $userService)
    {
        $this->loadUsers($userService);
    }

    public function loadUsers(UserService $userService)
    {
        $collection = $this->agencyId ? $userService->getByAgency($this->agencyId, $this->onlyActive) : collect([]);
        // Преобразуем коллекцию в массив с нужными полями и ставкой
        $this->users = $collection->map(function ($user) {
            return [
                'id' => $user->id,
                'login' => $user->login,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'roles' => $user->roles,
                'is_active' => $user->is_active,
                'rate_name' => $user->rate_name,
                'rate_value' => $user->rate_value,
            ];
        })->toArray();
    }
};
