<?php

namespace Src\Domain\CompletedWorks;

use DateTimeImmutable;
use Src\Domain\ValueObjects\DateTimeRange;

interface CompletedWorkRepositoryInterface
{
    /**
     * @return CompletedWork[]
     */
    public function findByProjectId(int $projectId, DateTimeRange $period): array;

    /**
     * @param  array{title: string, completed_at: DateTimeImmutable, sort_order?: int, depth?: int}[]  $works
     */
    public function saveMany(int $projectId, array $works): void;

    /**
     * @param  array{title: string, completed_at: DateTimeImmutable, sort_order?: int, depth?: int}[]  $works
     */
    public function replaceForPeriod(int $projectId, DateTimeRange $period, array $works): void;
}
