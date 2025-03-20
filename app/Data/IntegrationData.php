<?php

namespace App\Data;

use App\Enums\IntegrationCategory;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class IntegrationData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public string $name;
    public IntegrationCategory $category;
    public ?string $notification;
    public string $code;
}
