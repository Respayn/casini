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

    public static function options(): array
    {
        return array_map(
            fn (Kpi $kpi) => ['label' => $kpi->label(), 'value' => $kpi->value],
            self::cases()
        );
    }
}
