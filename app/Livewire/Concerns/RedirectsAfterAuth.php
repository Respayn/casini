<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Permission;

trait RedirectsAfterAuth
{
    protected function bindCurrentAgency(User $user): void
    {
        $agencyId = $user->agencies()->value('agencies.id');

        if ($agencyId) {
            Session::put('current_agency_id', (int) $agencyId);
        }
    }

    protected function homeRouteAfterAuth(): string
    {
        return route('channels', absolute: false);
    }

    /**
     * Минимальный доступ в «Каналы» после регистрации.
     * Полная модель прав — в отдельной задаче/ветке.
     */
    protected function grantRegistrationChannelsAccess(User $user): void
    {
        $permission = Permission::query()
            ->where('name', 'read channels')
            ->first();

        if ($permission) {
            $user->givePermissionTo($permission);
        }
    }
}
