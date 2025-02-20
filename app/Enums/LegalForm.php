<?php

namespace App\Enums;

enum LegalForm: string
{
    case LLC = 'llc';
    case SOLE_PROPRIETOR = 'sole_proprietor';

    public function label(): string 
    {
        return match ($this) {
            self::LLC => 'ООО',
            self::SOLE_PROPRIETOR => 'ИП'
        };
    }
}
