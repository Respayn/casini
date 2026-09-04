<?php

namespace App\Livewire\Users;

use App\Enums\UserAccountStatus;
use App\Services\AgencySettingsService;
use App\Services\UserService;
use App\Support\SystemSettingsSectionPermissions;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::system-settings')]
#[Title('Пользователи и роли')]
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

    #[Computed]
    public function canCreateUsers(): bool
    {
        return SystemSettingsSectionPermissions::userCanEdit(
            SystemSettingsSectionPermissions::users()
        );
    }

    public function updatedOnlyActive(UserService $userService)
    {
        $this->loadUsers($userService);
    }

    public function loadUsers(UserService $userService)
    {
        $collection = $this->agencyId ? $userService->getByAgency($this->agencyId, $this->onlyActive) : collect([]);
        $this->users = $collection->map(function ($user) {
            $isActive = (bool) $user->is_active;
            $status = $isActive
                ? UserAccountStatus::Active
                : ($user->email_verified_at === null
                    ? UserAccountStatus::PendingEmail
                    : UserAccountStatus::Inactive);

            return [
                'id' => $user->id,
                'login' => $user->login,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'roles' => $user->roles,
                'is_active' => $user->is_active,
                'account_status' => $status->value,
                'account_status_label' => $status->listLabel(),
                'rate_name' => $user->rate_name,
                'rate_value' => $user->rate_value,
            ];
        })->toArray();
    }
};
