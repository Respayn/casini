<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class RoleData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public int $id,
        public string $name,
        public ?string $displayName,
        public Collection $permissions = new Collection(),
        public bool $useInProjectFilter = false,
        public Collection $childRoles = new Collection(),
        public bool $hasAssignedUsers = false
    ) {}
}
