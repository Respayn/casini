<?php

namespace Src\Infrastructure\Reports;

/**
 * Подпись кампании в Word: из блока подмены utm_campaign или имя Директа.
 */
final class CampaignDisplayName
{
    /**
     * @param  array<string, string>  $utmCampaignLabels  номер кампании => подпись из клиенто-проекта
     */
    public static function resolve(?int $campaignId, string $directName, array $utmCampaignLabels): string
    {
        if ($campaignId === null || $campaignId <= 0) {
            return $directName;
        }

        $label = trim($utmCampaignLabels[(string) $campaignId] ?? '');

        return $label !== '' ? $label : $directName;
    }
}
