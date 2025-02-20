<?php

namespace App\Enums;

enum AttributionModel: string
{
    case FIRST = 'first';
    case LAST = 'last';
    case LASTSIGN = 'lastsign';
    case LAST_YANDEX_DIRECT_CLICK = 'last_yandex_direct_click ';
    case AUTOMATIC = 'automatic';

    public function label(): string
    {
        return match ($this) {
            self::FIRST => 'Первый источник',
            self::LAST => 'Последний источник',
            self::LASTSIGN => 'Последний значимый источник',
            self::LAST_YANDEX_DIRECT_CLICK => 'Последний переход из Директа',
            self::AUTOMATIC => 'Автоматическая атрибуция'
        };
    }
}
