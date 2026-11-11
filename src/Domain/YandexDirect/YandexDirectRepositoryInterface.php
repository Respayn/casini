<?php

namespace Src\Domain\YandexDirect;

use Src\Domain\ValueObjects\DateTimeRange;

interface YandexDirectRepositoryInterface
{
    /**
     * @return YandexDirectCampaignStats[]
     */
    public function findByProjectId(int $projectId, ?DateTimeRange $period = null): array;

    /**
     * Сумма дневных трат Директа за период. Нет строк → null.
     */
    public function sumDailySpendings(int $projectId, string $dateFrom, string $dateTo, bool $includeVat): ?float;

    /**
     * Клики кампаний по дням.
     *
     * @return array<string, int> Y-m-d => clicks
     */
    public function clicksByDay(int $projectId, string $dateFrom, string $dateTo): array;

    /**
     * Заменяет строки статистики кампаний за период.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function replaceCampaignStatsForPeriod(int $projectId, string $dateFrom, string $dateTo, array $rows): void;
}
