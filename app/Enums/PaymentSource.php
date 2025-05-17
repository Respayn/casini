<?php

namespace App\Enums;

enum PaymentSource: string
{
    case MANUAL = 'manual';
    case FROM_1C = '1C';

    public function label(): string
    {
        return match ($this) {
            static::MANUAL => 'Создано вручную',
            static::FROM_1C => 'Получено из 1С'
        };
    }
}

