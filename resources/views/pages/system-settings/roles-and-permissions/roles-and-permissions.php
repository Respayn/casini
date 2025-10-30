<?php

namespace App\Livewire\SystemSettings;

use App\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::system-settings')]
#[Title('Продукты и права')]
class extends Component
{
    private RoleService $roleService;

    public array $roles;

    public function boot(RoleService $roleService): void
    {
        $this->roleService = $roleService;
    }

    public function mount(): void
    {
        $this->roles = $this->roleService->getRolesAndPermissionsForSettingsPage();
    }

    public function save(): void
    {
        if (!Auth::user()->canAny(['edit system settings', 'full system settings'])) {
            // Toaster::error('Недостаточно прав!');
            return;
        }

        $result = $this->roleService->saveChanges($this->roles);
        if ($result->isFailure()) {
            // Toaster::error($result->getError());
        } else {
            // Toaster::success('Изменения сохранены!');
        }
    }

    #[Computed]
    public function defaultPermissions()
    {
        return $this->roleService->getPermissionsWithDefaultValuesForSettingsPage();
    }
};
