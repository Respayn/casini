<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class IntegrationData extends Data
{
    public int $id;
    public string $name;
    public string $category;
    public ?string $notification;
    public string $code;
}
