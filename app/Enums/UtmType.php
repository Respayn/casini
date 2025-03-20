<?php

namespace App\Enums;

enum UtmType: string
{
    case UTM_CAMPAIGN = 'utm_campaign';

    public function label(): string
    {
        return match ($this) {
            self::UTM_CAMPAIGN => 'UTM_Campaign',
        };
    }
}
