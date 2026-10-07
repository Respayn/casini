<?php

namespace App\Helpers;

final class PrimaryParameterPlanHelper
{
    public static function suffix(?string $parameterCode): ?string
    {
        return match ($parameterCode) {
            'leads' => 'лидов',
            'visits' => 'визитов',
            'top_percent' => 'позиций в топ 10',
            default => null,
        };
    }

    public static function label(?string $parameterCode): ?string
    {
        $suffix = self::suffix($parameterCode);

        return $suffix !== null ? '('.$suffix.')' : null;
    }
}
