<?php

namespace Src\Planning\Infrastructure;

use Src\Planning\Domain\Client;
use Src\Planning\Domain\Project;
use App\Models\Project as ProjectModel;
use App\Models\ProjectPlanApproval;
use App\Models\ProjectPlanValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Src\Planning\Application\Repositories\ProjectPlanRepositoryInterface;
use Src\Planning\Domain\ProjectPlan;
use Src\Planning\Domain\ValueObjects\PlanValue;
use Src\Shared\ValueObjects\KpiParameter;
use Src\Shared\ValueObjects\Quarter;

class EloquentProjectPlanRepository implements ProjectPlanRepositoryInterface
{
    public function findForYearByProject(int $projectId, int $year): ?ProjectPlan
    {
        $project = ProjectModel::with([
            'client',
            'planValues' => function ($query) use ($year) {
                $query->whereYear('year_month_date', $year);
            },
            'planApprovals' => function ($query) use ($year) {
                $query->where('year', $year);
            }
        ])->find($projectId);

        if (!$project) {
            return null;
        }

        return $this->mapToDomainModel($project, $year);
    }

    public function getAllPlansForYear(int $year): array
    {
        return $this->getProjects($year)
            ->map(fn($project) => $this->mapToDomainModel($project, $year))
            ->toArray();
    }

    public function getPlansByProjectIds(int $year, array $projectIds): array
    {
        return $this->getProjects($year, $projectIds)
            ->map(fn($project) => $this->mapToDomainModel($project, $year))
            ->toArray();
    }

    private function getProjects(int $year, array $projectIds = []): Collection
    {
        $query = ProjectModel::with([
            'client',
            'planValues' => function ($query) use ($year) {
                $query->whereYear('year_month_date', $year);
            },
            'planApprovals' => function ($query) use ($year) {
                $query->where('year', $year);
            }
        ]);

        if (!empty($projectIds)) {
            $query->whereIn('id', $projectIds);
        }

        return $query->get();
    }

    public function save(ProjectPlan $projectPlan): void
    {
        DB::transaction(function () use ($projectPlan) {
            $projectId = $projectPlan->getProject()->getId();
            $year = $projectPlan->getYear();

            $this->saveMonthlyValues($projectId, $year, $projectPlan->getAllMonthlyValues());
            $this->saveQuarterApprovals($projectId, $year, $projectPlan->getQuarterApprovals());
        });
    }

    public function saveAll(array $plans): void
    {
        $valuesData = [];
        $approvalsData = [];

        foreach ($plans as $plan) {
            $projectId = $plan->getProject()->getId();
            $year = $plan->getYear();

            foreach ($plan->getAllMonthlyValues() as $parameterCode => $months) {
                foreach ($months as $month => $value) {
                    $dateStr = sprintf('%d-%02d-01', $year, $month);

                    $valuesData[] = [
                        'project_id' => $projectId,
                        'parameter_code' => $parameterCode,
                        'year_month_date' => $dateStr,
                        'value' => $value
                    ];
                }
            }

            foreach ($plan->getQuarterApprovals() as $quarterNumber => $approved) {
                $approvalsData[] = [
                    'project_id' => $projectId,
                    'period' => 'quarter',
                    'year' => $year,
                    'period_number' => $quarterNumber,
                    'approved' => $approved
                ];
            }
        }

        DB::transaction(function () use ($valuesData, $approvalsData) {
            if (!empty($valuesData)) {
                foreach (array_chunk($valuesData, 1000) as $chunk) {
                    ProjectPlanValue::upsert(
                        $chunk,
                        ['project_id', 'parameter_code', 'year_month_date'],
                        ['value']
                    );
                }
            }

            if (!empty($approvalsData)) {
                ProjectPlanApproval::upsert(
                    $approvalsData,
                    ['project_id', 'period', 'year', 'period_number'],
                    ['approved']
                );
            }
        });
    }

    public function getMonthlyPlansForChannels(int $year, int $month, array $projectIds = []): array
    {
        $projectsQuery = ProjectModel::with([
            'client',
            'planValues' => function ($query) use ($year, $month) {
                $query->whereYear('year_month_date', $year)
                    ->whereMonth('year_month_date', $month);
            },
            'planApprovals' => function ($query) use ($year, $month) {
                $query->where('year', $year);
            }
        ]);

        if (!empty($projectIds)) {
            $projectsQuery->whereIn('project_id', $projectIds);
        }

        $projects = $projectsQuery->get();

        $plans = $projects->reduce(function ($carry, $project) use ($year, $month) {
            $planValues = $project->planValues->map(fn($pv) => new PlanValue(
                $pv->parameter_code,
                $year,
                $month,
                $pv->value
            ))->toArray();
            $domainProject = new Project(
                $project->id,
                $project->name,
                $project->created_at->toDateTimeImmutable(),
                $project->project_type,
                $project->kpi,
                planValues: $planValues
            );

            $carry[$project->id] = $domainProject->getPrimaryPlanValue($year, $month);
            return $carry;
        }, []);

        return $plans;
    }

    public function getMonthlyPlansForStatistics(int $year, int $month, array $projectIds = []): array
    {
        $projectsQuery = ProjectModel::with([
            'client',
            'planValues' => function ($query) use ($year, $month) {
                $query->whereYear('year_month_date', $year)
                    ->whereMonth('year_month_date', $month);
            },
            'planApprovals' => function ($query) use ($year, $month) {
                $query->where('year', $year);
            }
        ]);

        if (!empty($projectIds)) {
            $projectsQuery->whereIn('project_id', $projectIds);
        }

        $projects = $projectsQuery->get();

        $plans = $projects->reduce(function ($carry, $project) use ($year, $month) {
            $planValues = $project->planValues->map(fn($pv) => new PlanValue(
                $pv->parameter_code,
                $year,
                $month,
                $pv->value
            ))->toArray();
            $domainProject = new Project(
                $project->id,
                $project->name,
                $project->created_at->toDateTimeImmutable(),
                $project->project_type,
                $project->kpi,
                planValues: $planValues
            );

            $result = [];
            foreach ($domainProject->getParametersSchema()->getParameters() as $parameter) {
                $result[] = [
                    'value' => $domainProject->getPlanValue($parameter->getId(), $year, $month),
                    'format' => $parameter->getFormat()
                ];
            }

            $carry[$project->id] = $result;
            return $carry;
        }, []);

        return $plans;
    }

    private function mapToDomainModel(ProjectModel $eloquentProject, int $year): ProjectPlan
    {
        $client = new Client(
            $eloquentProject->client->id,
            $eloquentProject->client->name
        );

        $planValues = $eloquentProject->planValues->map(fn($pv) => new PlanValue(
            $pv->parameter_code,
            $year,
            $pv->year_month_date->month,
            $pv->value
        ))->toArray();

        $project = new Project(
            $eloquentProject->id,
            $eloquentProject->name,
            $eloquentProject->created_at->toDateTimeImmutable(),
            $eloquentProject->project_type,
            $eloquentProject->kpi,
            $client,
            $planValues
        );

        $plan = new ProjectPlan(
            $project,
            $year
        );

        foreach ($eloquentProject->planValues as $value) {
            $month = $value->year_month_date->month;
            $planValue = $value->value !== null ? (float)$value->value : null;

            $plan->setMonthlyValue($value->parameter_code, $month, $planValue);
        }

        foreach ($eloquentProject->planApprovals as $approval) {
            if ($approval->period === 'quarter') {
                $quarter = new Quarter($approval->period_number);
                $plan->setQuarterApproval($quarter, $approval->approved);
            }
        }

        return $plan;
    }

    private function saveMonthlyValues(int $projectId, int $year, array $monthlyValues): void
    {
        $upsertData = [];

        foreach ($monthlyValues as $parameterCode => $months) {
            foreach ($months as $month => $value) {
                $date = sprintf('%d-%02d-01', $year, $month);

                $upsertData[] = [
                    'project_id' => $projectId,
                    'parameter_code' => $parameterCode,
                    'year_month_date' => $date,
                    'value' => $value
                ];
            }
        }

        if (!empty($upsertData)) {
            ProjectPlanValue::upsert(
                $upsertData,
                ['project_id', 'parameter_code', 'year_month_date'],
                ['value']
            );
        }
    }

    private function saveQuarterApprovals(int $projectId, int $year, array $quarterApprovals): void
    {
        $data = [];
        foreach ($quarterApprovals as $quarterNumber => $approved) {
            $data[] = [
                'project_id' => $projectId,
                'period' => 'quarter',
                'year' => $year,
                'period_number' => $quarterNumber,
                'approved' => $approved
            ];
        }

        if (!empty($data)) {
            ProjectPlanApproval::upsert(
                $data,
                ['project_id', 'period', 'year', 'period_number'],
                ['approved']
            );
        }
    }
}
