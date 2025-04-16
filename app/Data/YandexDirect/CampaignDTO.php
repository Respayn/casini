<?php

namespace App\Data\YandexDirect;

class CampaignDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status
    ) {
    }
}
