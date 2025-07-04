<?php

namespace App\Data;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

// TODO: Пересмотреть DTO
class UserData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?int $id,
        public string $login,
        public string $name,
        public string $email,
        public array|Collection $roles = []
    ) {}
}
