<?php

namespace App\Services\Channels;

use App\Contracts\ChannelReportServiceInterface;
use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Enums\ChannelReportGrouping;
use App\Enums\ProjectType;
use App\Repositories\ClientRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;

class ChannelReportService implements ChannelReportServiceInterface
{
    private ClientRepository $clientRepository;
    private ProjectRepository $projectRepository;
    private UserRepository $userRepository;

    public function __construct(
        ClientRepository $clientRepository,
        ProjectRepository $projectRepository,
        UserRepository $userRepository
    ) {
        $this->clientRepository = $clientRepository;
        $this->projectRepository = $projectRepository;
        $this->userRepository = $userRepository;
    }

    public function getReportData(ChannelReportQueryData $query): TableReportData
    {
        $clients = $this->clientRepository->all();
        $projects = $this->projectRepository->all();
        $users = $this->userRepository->all();

        if (!$query->showInactive) {
            $projects = $projects->filter(fn($project) => $project->is_active);
        }

        if ($query->grouping === ChannelReportGrouping::NONE) {
            return $this->createFlatReport($clients, $projects, $users);
        }

        if ($query->grouping === ChannelReportGrouping::PROJECT_TYPE) {
            return $this->createReportGroupedByProjectType($clients, $projects, $users);
        }

        // TODO: delete
        $reportData = new TableReportData();
        $groupData = new TableReportGroupData();
        $groupData->rows = new Collection([
            new Collection([
                'department' => 1,
                'login' => 'Логин',
            ]),
            new Collection(['manager' => 'манагер']),
        ]);

        $reportData->groups = new Collection([$groupData]);

        return $reportData;
    }

    public function createFlatReport($clients, $projects, $users): TableReportData
    {
        $report = new TableReportData();
        $group = new TableReportGroupData();

        $rows = new Collection();

        foreach ($projects as $project) {
            $department = match ($project->project_type) {
                ProjectType::CONTEXT_AD => 'Контекст',
                ProjectType::SEO_PROMOTION => 'SEO'
            };

            $status = match ($project->is_active) {
                true => 'active',
                false => 'inactive'
            };

            $client = $clients->firstWhere('id', $project->client_id);

            $manager = $users->firstWhere('id', $client->manager_id);
            $managerName = $manager->first_name . ' ' . mb_substr($manager->last_name, 0, 1) . '.';

            $specialist = $users->firstWhere('id', $project->specialist_id);
            $specialistName = $specialist->first_name . ' ' . mb_substr($specialist->last_name, 0, 1) . '.';

            $kpi = $project->kpi->label();

            $rows->push(new Collection(array_merge(
                $this->createClientData($department, $client->name, $project->name, $project->id, $status),
                $this->createTeamData($managerName, $manager->id, $specialistName, $specialist->id),
                $this->createFinancialData($kpi, null, 0, 0, 0),
                $this->createSpendingsData(null, null, null, [])
            )));
        }

        $group->rows = $rows;
        $report->groups = new Collection([$group]);

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client_project' => [
                'count' => $projects->count()
            ],
            'status' => [
                'active' => $projects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projects->filter(fn($project) => !$project->is_active)->count()
            ]
        ]);

        return $report;
    }

    public function createReportGroupedByProjectType($clients, $projects, $users): TableReportData
    {
        $report = new TableReportData();
        $seoGroup = new TableReportGroupData();
        $seoGroup->groupLabel = 'SEO';
        $contextGroup = new TableReportGroupData();
        $contextGroup->groupLabel = 'Контекст';

        $seoRows = new Collection();
        $contextRows = new Collection();

        foreach ($projects as $project) {
            $department = match ($project->project_type) {
                ProjectType::CONTEXT_AD => 'Контекст',
                ProjectType::SEO_PROMOTION => 'SEO'
            };

            $status = match ($project->is_active) {
                true => 'active',
                false => 'inactive'
            };

            $client = $clients->firstWhere('id', $project->client_id);

            $manager = $users->firstWhere('id', $client->manager_id);
            $managerName = $manager->first_name . ' ' . mb_substr($manager->last_name, 0, 1) . '.';

            $specialist = $users->firstWhere('id', $project->specialist_id);
            $specialistName = $specialist->first_name . ' ' . mb_substr($specialist->last_name, 0, 1) . '.';

            $kpi = $project->kpi->label();

            if ($project->project_type === ProjectType::SEO_PROMOTION) {
                $seoRows->push(new Collection(array_merge(
                    $this->createClientData($department, $client->name, $project->name, $project->id, $status),
                    $this->createTeamData($managerName, $manager->id, $specialistName, $specialist->id),
                    $this->createFinancialData($kpi, null, 0, 0, 0),
                    $this->createSpendingsData(null, null, null, [])
                )));
            } else {
                $contextRows->push(new Collection(array_merge(
                    $this->createClientData($department, $client->name, $project->name, $project->id, $status),
                    $this->createTeamData($managerName, $manager->id, $specialistName, $specialist->id),
                    $this->createFinancialData($kpi, null, 0, 0, 0),
                    $this->createSpendingsData(null, null, null, [])
                )));
            }
        }

        $seoGroup->rows = $seoRows;
        $contextGroup->rows = $contextRows;

        $seoProjects = $projects->filter(fn($project) => $project->project_type === ProjectType::SEO_PROMOTION);
        $contextGroups = $projects->filter(fn($project) => $project->project_type === ProjectType::CONTEXT_AD);

        $seoGroup->summary = new Collection([
            'client' => [
                'count' => $seoProjects->pluck('client_id')->unique()->count()
            ],
            'client_project' => [
                'count' => $seoProjects->count()
            ],
            'status' => [
                'active' => $seoProjects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $seoProjects->filter(fn($project) => !$project->is_active)->count()
            ]
        ]);

        $contextGroup->summary = new Collection([
            'client' => [
                'count' => $contextGroups->pluck('client_id')->unique()->count()
            ],
            'client_project' => [
                'count' => $contextGroups->count()
            ],
            'status' => [
                'active' => $contextGroups->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $contextGroups->filter(fn($project) => !$project->is_active)->count()
            ]
        ]);

        $report->groups = new Collection([$seoGroup, $contextGroup]);

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client_project' => [
                'count' => $projects->count()
            ],
            'status' => [
                'active' => $projects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projects->filter(fn($project) => !$project->is_active)->count()
            ]
        ]);

        return $report;
    }

    public function createClientData(string $department, string $clientName, string $projectName, int $projectId, string $status): array
    {
        return [
            'department' => ['name' => $department],
            'client' => ['name' => $clientName],
            'client_project' => [
                'name' => $projectName,
                'id' => $projectId
            ],
            'client_project_id' => ['id' => $projectId],
            'status' => $status
        ];
    }

    public function createTeamData(string $managerName, int $managerId, string $specialistName, int $specialistId): array
    {
        return [
            'manager' => [
                'name' => $managerName,
                'id' => $managerId
            ],
            'specialist' => [
                'name' => $specialistName,
                'id' => $specialistId
            ]
        ];
    }

    public function createFinancialData(string $kpi, int|string|null $plan, int $clientReceipt, ?int $maxBonuses, ?int $acts): array
    {
        return [
            'kpi' => $kpi,
            'plan' => $plan,
            'client_receipt' => $clientReceipt,
            'max_bonuses' => $maxBonuses,
            'acts' => $acts
        ];
    }

    public function createSpendingsData(?array $programming, ?array $copyrighting, ?int $seoLinksSum, ?array $positions): array
    {
        $spendings = [
            'programming' => $programming,
            'copyrighting' => $copyrighting,
            'seo_links' => ['sum' => $seoLinksSum]
        ];

        foreach ($positions as $key => $position) {
            $spendings[$key] = $position;
        }

        $programmingSum = $programming ? $programming['sum'] : 0;
        $copyrightingSum = $copyrighting ? $copyrighting['sum'] : 0;

        if ($programming === null && $copyrighting === null && $seoLinksSum === null && $positions === null) {
            $totalSum = null;
        } else {
            $totalSum = $programmingSum + $copyrightingSum + $seoLinksSum;
            foreach ($positions as $position) {
                $totalSum += $position['sum'];
            }
        }

        $spendings['summary_spendings'] = ['sum' => $totalSum];

        return $spendings;
    }
}
