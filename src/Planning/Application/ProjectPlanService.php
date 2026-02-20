<?php

namespace Src\Planning\Application;

use Illuminate\Support\Arr;
use Src\Planning\Application\Repositories\ProjectPlanRepositoryInterface;
use Src\Planning\Application\Repositories\ProjectRepositoryInterface;
use Src\Planning\Application\Services\KpiParametersSchemaService;
use Src\Planning\Application\Services\PlanCalculator;
use Src\Planning\Domain\ProjectPlan;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;
use Src\Shared\ValueObjects\Quarter;

class ProjectPlanService
{
    private ProjectPlanRepositoryInterface $repository;
    private ProjectRepositoryInterface $projectRepository;
    private PlanCalculator $planCalculator;
    private KpiParametersSchemaService $schemaService;

    public function __construct(
        ProjectPlanRepositoryInterface $repository,
        ProjectRepositoryInterface $projectRepository,
        PlanCalculator $planCalculator,
        KpiParametersSchemaService $schemaService
    ) {
        $this->repository = $repository;
        $this->projectRepository = $projectRepository;
        $this->planCalculator = $planCalculator;
        $this->schemaService = $schemaService;
    }

    /**
     * Получение планов на год для всех проектов
     * @param int $year
     * @return array[]
     */
    public function getPlansForYear(int $year): array
    {
        $domainPlans = $this->repository->getAllPlansForYear($year);

        foreach ($domainPlans as $plan) {
            $projectType = $plan->getProject()->getType();
            $kpi = $plan->getProject()->getKpi();
            $this->planCalculator->recalculate($plan, $projectType, $kpi);
        }

        return array_map(fn(ProjectPlan $plan) => $this->mapToViewDto($plan), $domainPlans);
    }

    /**
     * Сохранение планов на год
     * @param int $year
     * @param array $plansData
     * @return void
     */
    public function savePlansForYear(int $year, array $plansData): void
    {
        $projectIds = array_column($plansData, 'project_id');
        if (empty($projectIds)) {
            return;
        }

        $plans = $this->repository->getPlansByProjectIds($year, $projectIds);
        $plansToSave = [];

        foreach ($plansData as $planData) {
            $projectId = $planData['project_id'];

            $plan = Arr::first($plans, fn($existingPlan) => $existingPlan->getProject()->getId() === $projectId);

            foreach ($planData['parameters'] as $paramData) {
                if (!$paramData['is_calculated']) {
                    foreach ($paramData['plans'] as $month => $value) {
                        if ($value !== '') {
                            $valueToSave = $value !== null ? (float)$value : null;
                            $plan->setMonthlyValue($paramData['key'], $month, $valueToSave);
                        }
                    }
                }
            }

            for ($quarterNum = 1; $quarterNum <= 4; $quarterNum++) {
                $quarter = new Quarter($quarterNum);
                $approved = $planData['approvals'][$quarterNum] ?? false;
                $plan->setQuarterApproval($quarter, $approved);
            }

            $plansToSave[] = $plan;
        }

        $this->repository->saveAll($plansToSave);
    }

    /**
     * Получение схемы параметров для страницы "статистика"
     * @param ProjectType $projectType
     * @param Kpi $kpi
     * @return array
     */
    public function getKpiParametersSchemaForStatistics(ProjectType $projectType, Kpi $kpi): array
    {
        $parametersSchema = $this->schemaService->createSchema($projectType, $kpi);

        return array_map(function ($parameter) {
            return [
                'name' => $parameter->getLabel(),
                'highlight' => false
            ];
        }, $parametersSchema->getParameters());
    }

    /**
     * Получение планов на месяц для всех проектов для страницы "Каналы"
     * @param int $year
     * @param int $month
     * @return array[]
    */
    public function getMonthlyPlansForChannels(int $year, int $month): array
    {
        return $this->repository->getMonthlyPlansForChannels($year, $month);
    }

    /**
     * Получение планов на месяц для всех проектов для страницы "статистика"
     * @param int $year
     * @param int $month
     * @return array[]
     */
    public function getMonthlyPlansForStatistics(int $year, int $month): array
    {
        return $this->repository->getMonthlyPlansForStatistics($year, $month);
    }

    /**
     * Summary of recalculateRow
     * @param array $rowData
     * @param int $year
     * @param int $month
     * @return array
     */
    public function recalculateRow(array $rowData, int $year, int $month): array
    {
        $project = $this->projectRepository->find($rowData['project_id']);
        $projectPlan = new ProjectPlan($project, $year);

        foreach ($rowData['parameters'] as $paramData) {
            foreach ($paramData['plans'] as $month => $value) {
                $projectPlan->setMonthlyValue($paramData['key'], $month, $value);
            }
        }

        $calculatedValues = $projectPlan->getAllMonthlyValues();

        foreach ($rowData['parameters'] as $index => $param) {
            $code = $param['key'];
            if (isset($calculatedValues[$code])) {
                $rowData['parameters'][$index]['plans'] = $calculatedValues[$code];
            }
        }

        return $rowData;
    }

    /**
     * Создание DTO для страницы "планирование"
     * @param ProjectPlan $plan
     * @return array
     */
    public function mapToViewDto(ProjectPlan $plan): array
    {
        $projectId = $plan->getProject()->getId();

        $project = $plan->getProject();
        $client = $project->getClient();
        $projectType = $project->getType();
        $kpi = $project->getKpi();

        $paramsSchema = $this->schemaService->createSchema(
            $projectType,
            $kpi
        );
        $parameters = [];

        foreach ($paramsSchema->getParameters() as $paramEnum) {
            $paramPlans = [];
            for ($month = 1; $month <= 12; $month++) {
                $paramPlans[$month] = $plan->getMonthlyValue($paramEnum->getId(), $month);
            }

            $parameters[] = [
                'key' => $paramEnum->getId(),
                'name' => $paramEnum->getLabel(),
                'format' => $paramEnum->getFormat(),
                'is_calculated' => $paramEnum->isCalculated(),
                'formula' => $paramEnum->getFormula(),
                'dependencies' => $paramEnum->getDependencies(),
                'plans' => $paramPlans
            ];
        }

        $approvals = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarter = new Quarter($q);
            $approvals[$q] = $plan->isQuarterApproved($quarter);
        }

        return [
            'client_id' => $client->getId(),
            'client_name' => $client->getName(),
            'project_id' => $projectId,
            'project_name' => $project->getName(),
            'project_created_at' => $project->getCreatedAt()->format('d.m.Y'),
            'department' => $projectType->shortLabel(),
            'kpi' => $kpi->label(),
            'parameters' => $parameters,
            'approvals' => $approvals
        ];
    }
}
