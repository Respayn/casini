<?php

namespace Src\Domain\CompletedWorks;

use DateTimeImmutable;
use Src\Domain\ValueObjects\DateTimeRange;

interface CompletedWorkRepositoryInterface
{
    /**
     * @param int $projectId
     * @param DateTimeRange $period
     * @return CompletedWork[]
     */
    public function findByProjectId(int $projectId, DateTimeRange $period): array;

    /**
     * @param int $projectId
     * @param array{title: string, completed_at: DateTimeImmutable}[] $works
     */
    public function saveMany(int $projectId, array $works): void;
}
