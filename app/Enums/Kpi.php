<?php

namespace App\Enums;

enum Kpi: string
{
    case TRAFFIC = 'traffic';
    case LEADS = 'leads';
    case POSITIONS = 'positions';

    public function label(): string
    {
        return match ($this) {
            self::TRAFFIC => 'Трафик',
            self::LEADS => 'Лиды',
            self::POSITIONS => 'Позиции'
        };
    }
}
