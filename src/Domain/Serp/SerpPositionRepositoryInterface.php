<?php

namespace Src\Domain\Serp;

use Src\Domain\ValueObjects\DateTimeRange;

interface SerpPositionRepositoryInterface
{
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

    /**
     * Позиции фраз по месяцам: для каждого месяца последняя проверка в этом месяце.
     *
     * @return list<array{phrase: string, position: int|null, month: string}>
     */
    public function getKeywordPositionsByMonth(
        int $projectId,
        DateTimeRange $period,
        string $searchEngineCode,
        int $regionGeoId,
    ): array;
}
