<?php

namespace Src\Planning\Application;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Src\Domain\ValueObjects\Quarter;
use Src\Planning\Application\Repositories\ProjectPlanRepositoryInterface;
use Src\Planning\Application\Repositories\ProjectRepositoryInterface;
use Src\Planning\Application\Services\KpiParametersSchemaService;
use Src\Planning\Application\Services\PlanCalculator;
use Src\Planning\Domain\ProjectPlan;

class ProjectPlanService
{
    private ProjectPlanRepositoryInterface $repository;

    private ProjectRepositoryInterface $projectRepository;

    private PlanCalculator $planCalculator;

    private KpiParametersSchemaService $schemaService;

    /** @var array<int, string|null> */
    private array $approverNameCache = [];

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
     *
     * @return array[]
     */
    public function getPlansForYear(int $year, ?int $projectId = null, bool $showInactive = false): array
    {
        // Фильтр одного клиенто-проекта из сайдбара: показываем его даже если неактивный
        // (как на Каналах/Статистике при выбранном projectId).
        if ($projectId !== null) {
            $domainPlans = $this->repository->getPlansByProjectIds($year, [$projectId]);
        } else {
            $domainPlans = $this->repository->getAllPlansForYear($year, $showInactive);
        }

        foreach ($domainPlans as $plan) {
            $projectType = $plan->getProject()->getType();
            $kpi = $plan->getProject()->getKpi();
            $this->planCalculator->recalculate($plan, $projectType, $kpi);
        }

        return array_map(fn (ProjectPlan $plan) => $this->mapToViewDto($plan), $domainPlans);
    }

    /**
     * Сохранение планов на год
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

            $plan = Arr::first($plans, fn ($existingPlan) => $existingPlan->getProject()->getId() === $projectId);

            foreach ($planData['parameters'] as $paramData) {
                if (! $paramData['is_calculated']) {
                    foreach ($paramData['plans'] as $month => $value) {
                        if ($value !== '') {
                            $valueToSave = $value !== null ? (float) $value : null;
                            if ($valueToSave !== null && $this->shouldRoundParameter($paramData)) {
                                $valueToSave = round($valueToSave);
                            }
                            $plan->setMonthlyValue($paramData['key'], $month, $valueToSave);
                        }
                    }
                }
            }

            $this->planCalculator->recalculate(
                $plan,
                $plan->getProject()->getType(),
                $plan->getProject()->getKpi()
            );

            for ($quarterNum = 1; $quarterNum <= 4; $quarterNum++) {
                $quarter = new Quarter($quarterNum);
                $approvalData = $planData['approvals'][$quarterNum] ?? false;
                $approved = is_array($approvalData)
                    ? (bool) ($approvalData['approved'] ?? false)
                    : (bool) $approvalData;
                $approvedAt = is_array($approvalData)
                    ? ($approved ? ($approvalData['approved_at'] ?? null) : null)
                    : null;
                $approvedBy = is_array($approvalData)
                    ? ($approved ? ($approvalData['approved_by'] ?? null) : null)
                    : null;

                if ($approved && $approvedAt === null) {
                    $approvedAt = now()->toDateString();
                }

                if ($approved && $approvedBy === null) {
                    $approvedBy = Auth::id();
                }

                $plan->setQuarterApproval($quarter, $approved, $approvedAt, $approvedBy ? (int) $approvedBy : null);
            }

            $plansToSave[] = $plan;
        }

        $this->repository->saveAll($plansToSave);
    }

    /**
     * Получение схемы параметров для страницы "статистика"
     */
    public function getKpiParametersSchemaForStatistics(ProjectType $projectType, Kpi $kpi): array
    {
        $parametersSchema = $this->schemaService->createSchema($projectType, $kpi);

        return array_map(function ($parameter) {
            return [
                'name' => $parameter->getLabel(),
                'highlight' => $parameter->isPrimary(),
            ];
        }, $parametersSchema->getParameters());
    }

    /**
     * Коды параметров KPI в том же порядке, что и схема для Статистики/Каналов.
     *
     * @return list<string>
     */
    public function getParameterCodes(ProjectType $projectType, Kpi $kpi): array
    {
        return array_map(
            fn ($p) => $p->getId(),
            $this->schemaService->createSchema($projectType, $kpi)->getParameters()
        );
    }

    /**
     * Получение планов на месяц для всех проектов для страницы "Каналы"
     *
     * @return array[]
     */
    public function getMonthlyPlansForChannels(int $year, int $month): array
    {
        return $this->repository->getMonthlyPlansForChannels($year, $month);
    }

    /**
     * Получение планов на месяц для всех проектов для страницы "статистика"
     *
     * @return array[]
     */
    public function getMonthlyPlansForStatistics(int $year, int $month): array
    {
        return $this->repository->getMonthlyPlansForStatistics($year, $month);
    }

    /**
     * Summary of recalculateRow
     */
    public function recalculateRow(array $rowData, int $year, int $month): array
    {
        $project = $this->projectRepository->find($rowData['project_id']);
        $projectPlan = new ProjectPlan($project, $year);

        foreach ($rowData['parameters'] as $paramData) {
            if (! empty($paramData['is_calculated'])) {
                continue;
            }

            foreach ($paramData['plans'] as $planMonth => $value) {
                $valueToSet = $value !== null && $value !== '' ? (float) $value : null;
                if ($valueToSet !== null && $this->shouldRoundParameter($paramData)) {
                    $valueToSet = round($valueToSet);
                }
                $projectPlan->setMonthlyValue($paramData['key'], $planMonth, $valueToSet);
            }
        }

        $this->planCalculator->recalculate(
            $projectPlan,
            $project->getType(),
            $project->getKpi()
        );

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
                'highlight' => $paramEnum->isPrimary(),
                'plans' => $paramPlans,
            ];
        }

        $approvals = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarter = new Quarter($q);
            $approved = $plan->isQuarterApproved($quarter);
            $approvedAt = $plan->getQuarterApprovedAt($quarter);
            $approvedBy = $plan->getQuarterApprovedBy($quarter);
            $approvedByName = $approved ? $this->resolveApproverName($approvedBy) : null;

            $approvals[$q] = [
                'approved' => $approved,
                'approved_at' => $approvedAt,
                'approved_by' => $approvedBy,
                'approved_by_name' => $approvedByName,
                'date' => ($approved && $approvedAt)
                    ? Carbon::parse($approvedAt)->format('d.m.y')
                    : null,
            ];
        }

        return [
            'client_id' => $client->getId(),
            'client_name' => $client->getName(),
            'project_id' => $projectId,
            'project_name' => $project->getName(),
            'project_created_at' => $project->getCreatedAt()->format('d.m.Y'),
            'department' => $projectType->label(),
            'kpi' => $kpi->label(),
            'parameters' => $parameters,
            'approvals' => $approvals,
        ];
    }

    /**
     * @param  array{format?: string|null, key?: string}  $paramData
     */
    private function shouldRoundParameter(array $paramData): bool
    {
        $format = $paramData['format'] ?? null;

        return in_array($format, ['integer', 'percent'], true);
    }

    private function resolveApproverName(?int $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        if (! array_key_exists($userId, $this->approverNameCache)) {
            $user = User::query()->find($userId);
            $this->approverNameCache[$userId] = $user
                ? trim($user->first_name.' '.$user->last_name)
                : null;
        }

        return $this->approverNameCache[$userId] ?: null;
    }
}
