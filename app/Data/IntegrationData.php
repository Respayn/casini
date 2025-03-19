<?php

namespace App\Data;

use App\Enums\IntegrationCategory;
use Spatie\LaravelData\Data;

class IntegrationData extends Data
{
    public int $id;
    public string $name;
    public IntegrationCategory $category;
    public ?string $notification;
    public string $code;
}
