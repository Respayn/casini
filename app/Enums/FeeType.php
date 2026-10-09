<?php

namespace App\Enums;

enum FeeType: string
{
    case THREE_PERCENT = 'three_percent';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::THREE_PERCENT => 'Сбор 3%',
            self::NONE => 'Без сбора',
        };
    }

    /**
     * Сбор внутри суммы пополнения: в кабинет уходит сумма / 1,03, остаток - сбор.
     */
    public function feeFrom(float $topUpAmount): float
    {
        if ($this === self::NONE || $topUpAmount <= 0) {
            return 0.0;
        }

        return round($topUpAmount - round($topUpAmount / 1.03, 2), 2);
    }
}
