<?php

namespace App\Data\Sidebar;

use App\Data\ClientData;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class EmployeeData extends Data implements Wireable
{
    use WireableData;

    /**
     * @param array<int, ClientData> $clients
     */
    public function __construct(
      public int $id,
      public string $name,
      public array $clients,
      public bool $open = false
    ) {}
}
