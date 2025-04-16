<?php

namespace App\Data\YandexDirect;

class CampaignStatisticsDTO
{
    public function __construct(
        public readonly int $campaignId,
        public readonly string $date,
        public readonly int $clicks,
        public readonly float $cost
    ) {
    }
}
