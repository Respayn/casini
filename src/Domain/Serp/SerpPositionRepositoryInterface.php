<?php

namespace Src\Domain\Serp;

use Src\Domain\ValueObjects\DateTimeRange;

interface SerpPositionRepositoryInterface
{
    /**
     * Рассчитывает процент ключевых фраз, попавших в топ‑3, топ‑5, топ‑10.
     *
     * @return array{top_3: float, top_5: float, top_10: float}
     */
    public function getTopPercentages(
        int $projectId,
        DateTimeRange $period,
        string $searchEngineCode,
        int $regionGeoId,
    ): array;

    /**
     * Получает ключевые фразы с последними позициями за период.
     *
     * @return array<int, array{phrase: string, position: int|null}>
     */
    public function getKeywordPositions(
        int $projectId,
        DateTimeRange $period,
        string $searchEngineCode,
        int $regionGeoId,
    ): array;
}
