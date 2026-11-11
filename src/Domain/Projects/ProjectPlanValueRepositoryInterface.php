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
     * @param  array<string>  $parameterCodes
     * @return array<string, ProjectPlanValue|null> Код параметра => плановое значение или null
     */
    public function findByCodes(int $projectId, array $parameterCodes, DateTimeRange $period): array;

    /**
     * Сумма планов показателя за все месяцы периода. Нет строк → 0.
     */
    public function sumByCode(int $projectId, string $parameterCode, DateTimeRange $period): int;
}
