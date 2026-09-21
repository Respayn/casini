<?php

namespace Src\Planning\Application\Repositories;

use Src\Planning\Domain\ProjectPlan;

interface ProjectPlanRepositoryInterface
{
    public function findForYearByProject(int $projectId, int $year): ?ProjectPlan;

    /**
     * @param  bool  $showInactive  если false — только активные клиенто-проекты
     * @return ProjectPlan[]
     */
    public function getAllPlansForYear(int $year, bool $showInactive = false): array;

    /**
     * @return ProjectPlan[]
     */
    public function getPlansByProjectIds(int $year, array $projectIds): array;

    public function save(ProjectPlan $projectPlan): void;

    /**
     * @param  ProjectPlan[]  $plans
     */
    public function saveAll(array $plans): void;

    public function getMonthlyPlansForChannels(int $year, int $month, array $projectIds = []): array;

    public function getMonthlyPlansForStatistics(int $year, int $month, array $projectIds = []): array;
}
