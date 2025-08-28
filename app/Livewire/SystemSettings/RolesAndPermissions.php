<?php

namespace App\Livewire\SystemSettings;

use App\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

#[Layout('components.layouts.system-settings')]
class RolesAndPermissions extends Component
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
            Toaster::error('Недостаточно прав!');
            return;
        }

        $result = $this->roleService->saveChanges($this->roles);
        if ($result->isFailure()) {
            Toaster::error($result->getError());
        } else {
            Toaster::success('Изменения сохранены!');
        }
    }

    public function render()
    {
        $defaultPermissions = $this->roleService->getPermissionsWithDefaultValuesForSettingsPage();
        return view('livewire.system-settings.roles-and-permissions', compact('defaultPermissions'));
    }
}
