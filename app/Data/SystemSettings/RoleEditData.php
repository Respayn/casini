<?php

namespace App\Data\SystemSettings;

use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class RoleEditData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public string $id,
        public string $name,
        public array|Collection $permissions,
        public bool $useInProjectFilter,
        public bool $useInManagersList,
        public bool $useInSpecialistList,
        public bool $hasChildRoles,
        public array|Collection $childRoles
    ) {
        if (is_array($permissions)) {
            $this->permissions = collect($permissions);
        }

        if (is_array($childRoles)) {
            $this->childRoles = collect($childRoles);
        }
    }
}