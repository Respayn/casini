<?php

namespace App\Data\Sidebar;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ClientData extends Data implements Wireable
{
    use WireableData;

    /**
     * @param string $name
     * @param array<int, ProjectData> $projects
     */
    public function __construct(
      public int $id,
      public string $name,
      public array $projects,
      public bool $open = false
    ) {}
}
