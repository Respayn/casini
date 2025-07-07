<?php

namespace App\Services;

use Spatie\Permission\Models\Role;

class RoleService
{
    public function getRoleOptions(): array
    {
        return Role::query()
            ->orderBy('id')
            ->get()
            ->map(function ($role) {
                $enum = \App\Enums\Role::tryFrom($role->name);
                return [
                    'label' => $enum ? $enum->label() : $role->name,
                    'value' => $role->id, // если в форме нужен id
                    'name'  => $role->name // если нужен name
                ];
            })->toArray();
    }
}
