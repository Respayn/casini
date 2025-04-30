<?php

namespace App\Data\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class StringToBoolCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
