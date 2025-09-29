<?php

namespace App\Data;

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
        public string $email,
        /** @var Collection<int, Role> */
        public Collection $roles = new Collection(),
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?bool $is_active = null,
        public ?string $rate_name = null,
        public ?int $rate_value = null,
    ) {}
}
