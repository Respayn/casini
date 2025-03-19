<?php

namespace App\Enums;

enum IntegrationCategory: string
{
    case MONEY = 'money';
    case ANALYTICS = 'analytics';
    case TOOLS = 'tools';

    public function label(): string
    {
        return match ($this) {
            self::MONEY => 'Деньги',
            self::ANALYTICS => 'Аналитика',
            self::TOOLS => 'Инструменты',
        };
    }
}
