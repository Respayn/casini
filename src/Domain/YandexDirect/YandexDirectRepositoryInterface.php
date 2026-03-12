<?php

namespace Src\Domain\YandexDirect;

use Src\Domain\ValueObjects\DateTimeRange;

interface YandexDirectRepositoryInterface
{
    /**
     * @param int $projectId
     * @param DateTimeRange|null $period
     * @return YandexDirectCampaignStats[]
     */
    public function findByProjectId(int $projectId, ?DateTimeRange $period = null): array;
}
