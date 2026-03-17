<?php

namespace App\Enums;

enum Department: int
{
    case SEO = 1;
    case CA = 2;

    public function label(): string
    {
        return match ($this) {
            static::SEO => 'SEO',
            static::CA => 'КР'
        };
    }
}
