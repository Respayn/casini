<?php

namespace App\Data\SystemSettings;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class PermissionEditData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public string $name,
        public string $displayName,
        public bool $canRead,
        public bool $canEdit,
        public bool $haveFullAccess,
        public bool $isSecondary = false
    ) {}
}