<?php

namespace Src\Domain\Serp;

use Src\Domain\ValueObjects\DateTimeRange;

interface SerpPositionRepositoryInterface
{
    /**
     * Рассчитывает процент ключевых фраз, попавших в топ‑3, топ‑5, топ‑10.
     *
     * @param int $projectId
     * @param DateTimeRange $period
     * @param string $searchEngineCode
     * @return array{top_3: float, top_5: float, top_10: float}
     */
    public function getTopPercentages(
        int $projectId,
        DateTimeRange $period,
        string $searchEngineCode = 'yandex'
    ): array;
}