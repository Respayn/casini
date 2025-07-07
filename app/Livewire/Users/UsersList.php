<?php

namespace App\Livewire\Users;

use App\Services\UserService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.system-settings')]
class UsersList extends Component
{
    public bool $onlyActive = true;
    public array $users = [];
    public ?int $agencyId = null;

    public function mount(UserService $userService)
    {
        $this->agencyId = session('current_agency_id');
        $this->loadUsers($userService);
    }

    public function updatedOnlyActive(UserService $userService)
    {
        $this->loadUsers($userService);
    }

    public function loadUsers(UserService $userService)
    {
        $collection = $userService->getByAgency($this->agencyId, $this->onlyActive);
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

    public function render()
    {
        return view('livewire.system-settings.users.users-list', [
            'users' => $this->users,
        ]);
    }
}
