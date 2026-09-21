<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Session;

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
}
