<?php

namespace App\Data;

use App\Models\User;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class UserData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
    ) {
    }
}
