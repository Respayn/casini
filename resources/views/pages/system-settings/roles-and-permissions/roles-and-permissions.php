<?php

namespace App\Livewire\SystemSettings;

use App\Services\RoleService;
use App\Support\SystemSettingsSectionPermissions;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Exceptions\UnauthorizedException;

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
        if (! SystemSettingsSectionPermissions::userCanEdit(
            SystemSettingsSectionPermissions::rolesAndPermissions(),
            Auth::user()
        )) {
            throw UnauthorizedException::forPermissions(
                SystemSettingsSectionPermissions::editPermissionNames(
                    SystemSettingsSectionPermissions::rolesAndPermissions()
                )
            );
        }

        $result = $this->roleService->saveChanges($this->roles);
        if ($result->isFailure()) {
            // Toaster::error($result->getError());
        } else {
            // Toaster::success('Изменения сохранены!');
        }
    }

    #[Computed]
    public function canEditRolesAndPermissions(): bool
    {
        return SystemSettingsSectionPermissions::userCanEdit(
            SystemSettingsSectionPermissions::rolesAndPermissions(),
            Auth::user()
        );
    }

    #[Computed]
    public function defaultPermissions()
    {
        return $this->roleService->getPermissionsWithDefaultValuesForSettingsPage();
    }
};
