<?php

namespace App\Data\YandexDirect;

class CampaignStatisticsDTO
{
    public function __construct(
        public string $date,
        public int $campaignId,
        public int $clicks,
        public float $cost
    ) {}
}
