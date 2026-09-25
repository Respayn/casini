<?php

namespace App\Support;

final class Bitrix24AgencyConnection
{
    public static function isConfigured(?string $portalUrl, ?string $webhook): bool
    {
        return filled($portalUrl) && filled($webhook);
    }
}
