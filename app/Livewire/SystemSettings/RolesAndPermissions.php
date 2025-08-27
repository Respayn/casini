<?php

namespace App\Livewire\SystemSettings;

use App\Services\RoleService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.system-settings')]
class RolesAndPermissions extends Component
{
    /**
     * Модель данных:
     * - список ролей
     * - список прав
     * - текущие значения прав для роли
     */

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
        $result = $this->roleService->saveChanges($this->roles);
        if ($result->isFailure()) {
            $this->js("alert('" . $result->getError() . "')");
        }
    }

    public function render()
    {
        $defaultPermissions = $this->roleService->getPermissionsWithDefaultValuesForSettingsPage();
        return view('livewire.system-settings.roles-and-permissions', compact('defaultPermissions'));
    }
}
