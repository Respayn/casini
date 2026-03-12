<?php

namespace Src\Domain\Projects;

use Src\Domain\ValueObjects\DateTimeRange;

interface ProjectPlanValueRepositoryInterface
{
    /**
     * Получает плановое значение по коду параметра и месяцу.
     */
    public function findByCodeAndMonth(int $projectId, string $parameterCode, DateTimeRange $period): ?ProjectPlanValue;

    /**
     * Получает плановые значения по кодам параметров за период.
     *
     * @param int $projectId
     * @param array<string> $parameterCodes
     * @param DateTimeRange $period
     * @return array<string, ProjectPlanValue|null> Код параметра => плановое значение или null
     */
    public function findByCodes(int $projectId, array $parameterCodes, DateTimeRange $period): array;
}
