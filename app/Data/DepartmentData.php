<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class DepartmentData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public string $name;
    public ?string $description;
}
