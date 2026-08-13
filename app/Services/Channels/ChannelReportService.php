<?php

namespace App\Services\Channels;

use App\Contracts\ChannelReportServiceInterface;
use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use App\Enums\ChannelReportGrouping;
use App\Repositories\ClientRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\RateRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Src\Planning\Application\ProjectPlanService;
use Src\Domain\ValueObjects\ProjectType;

class ChannelReportService implements ChannelReportServiceInterface
{
    private ClientRepository $clientRepository;
    private ProjectRepository $projectRepository;
    private UserRepository $userRepository;
    private IntegrationRepository $integrationRepository;
    private RateRepository $rateRepository;
    private ProjectPlanService $projectPlanService;
    private ChannelDirectMetricsService $directMetricsService;

    public function __construct(
        ClientRepository $clientRepository,
        ProjectRepository $projectRepository,
        UserRepository $userRepository,
        IntegrationRepository $integrationRepository,
        RateRepository $rateRepository,
        ProjectPlanService $projectPlanService,
        ChannelDirectMetricsService $directMetricsService
    ) {
        $this->clientRepository = $clientRepository;
        $this->projectRepository = $projectRepository;
        $this->userRepository = $userRepository;
        $this->integrationRepository = $integrationRepository;
        $this->rateRepository = $rateRepository;
        $this->projectPlanService = $projectPlanService;
        $this->directMetricsService = $directMetricsService;
    }

    public function getUserSettings(int $userId): ChannelReportQueryData
    {
        // TODO: move fetch logic to repository
        $rates = $this->rateRepository->getRatesWithEnabledSpendingsTimeFetching();

        $savedSettings = DB::table('channel_report_user_settings')
            ->where('user_id', $userId)
            ->value('settings');

        if ($savedSettings) {
            return ChannelReportQueryData::hydrateFromSavedSettings($savedSettings, $rates);
        }

        return ChannelReportQueryData::create($rates);
    }

    public function saveUserSettings(int $userId, ChannelReportQueryData $settings): void
    {
        // TODO: move save logic to repository
        DB::table('channel_report_user_settings')
            ->updateOrInsert(
                ['user_id' => $userId],
                ['settings' => $settings->toJson()]
            );
    }

    public function getReportData(ChannelReportQueryData $query): TableReportData
    {
        $user = Auth::user();

        $clients = $this->clientRepository->all();
        if ($user->isManager() && !$user->hasAnyPermission(['read clients and projects all', 'full clients and projects all'])) {
            $clients = $clients->filter(fn($client) => $client->manager_id === $user->id);
        }

        $projects = $this->projectRepository->all();
        $projects = $projects->filter(fn($project) => $clients->pluck('id')->contains($project->client_id));
        if ($user->isSpecialist() && !$user->hasAnyPermission(['read clients and projects all', 'full clients and projects all'])) {
            $projects = $projects->filter(fn($project) => $project->specialist_id === $user->id);
        }

        $users = $this->userRepository->all();
        $integrations = $this->integrationRepository->getActiveIntegrationsMappedByProjects($projects->pluck('id'));

        $plans = $query->isSingleMonthPeriod()
            ? $this->projectPlanService->getMonthlyPlansForChannels($query->dateFrom->year, $query->dateFrom->month)
            : [];

        if (!$query->showInactive) {
            $projects = $projects->filter(fn($project) => $project->is_active);
        }

        // TODO: разнести логику по соответствующим классам
        if ($query->grouping === ChannelReportGrouping::PROJECT_TYPE) {
            $report = $this->createReportGroupedByProjectType($clients, $projects, $users, $integrations, $plans);
        } elseif ($query->grouping === ChannelReportGrouping::CLIENTS) {
            $report = $this->createReportGroupedByClients($clients, $projects, $users, $integrations, $plans);
        } elseif ($query->grouping === ChannelReportGrouping::TOOLS) {
            $report = $this->createReportGroupedByTools($clients, $projects, $users, $integrations, $plans);
        } elseif ($query->grouping === ChannelReportGrouping::ROLE) {
            $report = $this->createReportGroupedByRoles($clients, $projects, $users, $integrations, $plans);
        } else {
            $report = $this->createFlatReport($clients, $projects, $users, $integrations, $plans);
        }

        $this->enrichWithDirectMetrics($report, $query, $integrations);

        return $report;
    }

    private function enrichWithDirectMetrics(
        TableReportData $report,
        ChannelReportQueryData $query,
        Collection $integrations
    ): void {
        $budgetTotal = null;
        $spendingsTotal = null;

        foreach ($report->groups as $group) {
            $groupBudget = null;
            $groupSpendings = null;

            foreach ($group->rows as $row) {
                $projectIntegrations = $integrations->get($row->id, collect());

                $budgetParams = $this->directMetricsService->budgetCellParams(
                    (int) $row->id,
                    $projectIntegrations
                );
                $spendingsParams = $this->directMetricsService->spendingsCellParams(
                    (int) $row->id,
                    $projectIntegrations,
                    $query->dateFrom,
                    $query->dateTo,
                    $query->includeVat
                );

                $row->data->put('direct-budget', $budgetParams);
                $row->data->put('direct-spendings', $spendingsParams);

                if ($budgetParams['value'] !== null) {
                    $groupBudget = ($groupBudget ?? 0) + $budgetParams['value'];
                    $budgetTotal = ($budgetTotal ?? 0) + $budgetParams['value'];
                }

                if ($spendingsParams['value'] !== null) {
                    $groupSpendings = ($groupSpendings ?? 0) + $spendingsParams['value'];
                    $spendingsTotal = ($spendingsTotal ?? 0) + $spendingsParams['value'];
                }
            }

            if ($group->summary instanceof Collection) {
                $group->summary->put('direct-budget', $groupBudget);
                $group->summary->put('direct-spendings', $groupSpendings);
            }
        }

        if ($report->summary instanceof Collection) {
            $report->summary->put('direct-budget', $budgetTotal);
            $report->summary->put('direct-spendings', $spendingsTotal);
        }
    }

    public function createFlatReport($clients, $projects, $users, Collection $integrations, array $plans): TableReportData
    {
        $report = new TableReportData();
        $group = new TableReportGroupData();

        $rows = new Collection();

        foreach ($projects as $project) {
            $row = new TableReportRowData();
            $row->id = $project->id;

            $projectTypeLabel = $project->project_type->label();

            $status = match ($project->is_active) {
                true => 'active',
                false => 'inactive'
            };

            $team = $this->resolveProjectTeam($clients, $users, $project);
            $client = $team['client'];
            $manager = $team['manager'];
            $specialist = $team['specialist'];

            $kpi = $project->kpi?->label() ?? '—';

            $projectIntegrations = $integrations->get($project->id, []);

            $plan = isset($plans[$project->id]) ? $plans[$project->id] : null;

            $row->data = new Collection(array_merge(
                $this->createClientData($projectTypeLabel, $client?->name ?? '—', $project->name, $project->id, $status),
                $this->createTeamData($team['managerName'], $manager?->id, $team['specialistName'], $specialist?->id),
                $this->createFinancialData($kpi, $plan, $project->bonusCondition?->client_payment, 0, 0),
                $this->createSpendingsData(null, null, null, []),
                $this->createIntegrationData($projectIntegrations)
            ));

            $rows->push($row);
        }

        $group->rows = $rows;
        $report->groups = new Collection([$group]);

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $projects->count()
            ],
            'status' => [
                'active' => $projects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projects->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => $integrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
        ]);

        return $report;
    }

    private function formatUserShortName(?object $user): string
    {
        if ($user === null) {
            return '—';
        }

        $lastInitial = filled($user->last_name ?? null)
            ? mb_substr((string) $user->last_name, 0, 1).'.'
            : '';

        $name = trim(((string) ($user->first_name ?? '')).' '.$lastInitial);

        return $name !== '' ? $name : '—';
    }

    /**
     * @return array{client: mixed, manager: mixed, specialist: mixed, managerName: string, specialistName: string}
     */
    private function resolveProjectTeam($clients, $users, $project): array
    {
        $client = $clients->firstWhere('id', $project->client_id);
        $manager = $client ? $users->firstWhere('id', $client->manager_id) : null;
        $specialist = $users->firstWhere('id', $project->specialist_id);

        return [
            'client' => $client,
            'manager' => $manager,
            'specialist' => $specialist,
            'managerName' => $this->formatUserShortName($manager),
            'specialistName' => $this->formatUserShortName($specialist),
        ];
    }

    public function createReportGroupedByProjectType($clients, $projects, $users, $integrations, array $plans): TableReportData
    {
        $report = new TableReportData();
        $seoGroup = new TableReportGroupData();
        $seoGroup->groupLabel = ProjectType::SEO_PROMOTION->label();
        $contextGroup = new TableReportGroupData();
        $contextGroup->groupLabel = ProjectType::CONTEXT_AD->label();

        $seoRows = new Collection();
        $contextRows = new Collection();

        foreach ($projects as $project) {
            $row = new TableReportRowData();
            $row->id = $project->id;

            $projectTypeLabel = $project->project_type->label();

            $status = match ($project->is_active) {
                true => 'active',
                false => 'inactive'
            };

            $team = $this->resolveProjectTeam($clients, $users, $project);
            $client = $team['client'];
            $manager = $team['manager'];
            $specialist = $team['specialist'];

            $kpi = $project->kpi?->label() ?? '—';

            $projectIntegrations = $integrations->get($project->id, []);

            $plan = isset($plans[$project->id]) ? $plans[$project->id] : null;

            $row->data = new Collection(array_merge(
                $this->createClientData($projectTypeLabel, $client?->name ?? '—', $project->name, $project->id, $status),
                $this->createTeamData($team['managerName'], $manager?->id, $team['specialistName'], $specialist?->id),
                $this->createFinancialData($kpi, $plan, $project->bonusCondition?->client_payment, 0, 0),
                $this->createSpendingsData(null, null, null, []),
                $this->createIntegrationData($projectIntegrations)
            ));

            if ($project->project_type === ProjectType::SEO_PROMOTION) {
                $seoRows->push($row);
            } else {
                $contextRows->push($row);
            }
        }

        $seoGroup->rows = $seoRows;
        $contextGroup->rows = $contextRows;

        $seoProjects = $projects->filter(fn($project) => $project->project_type === ProjectType::SEO_PROMOTION);
        $contextProjects = $projects->filter(fn($project) => $project->project_type === ProjectType::CONTEXT_AD);

        $seoIntegrations = $integrations->filter(function ($integrations, $projectId) use ($seoProjects) {
            return $seoProjects->pluck('id')->contains($projectId);
        });
        $contextIntegrations = $integrations->filter(function ($integrations, $projectId) use ($contextProjects) {
            return $contextProjects->pluck('id')->contains($projectId);
        });

        $seoGroup->summary = new Collection([
            'client' => [
                'count' => $seoProjects->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $seoProjects->count()
            ],
            'status' => [
                'active' => $seoProjects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $seoProjects->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => $seoIntegrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
        ]);

        $contextGroup->summary = new Collection([
            'client' => [
                'count' => $contextProjects->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $contextProjects->count()
            ],
            'status' => [
                'active' => $contextProjects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $contextProjects->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => $contextIntegrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
        ]);

        $report->groups = new Collection([$seoGroup, $contextGroup]);

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $projects->count()
            ],
            'status' => [
                'active' => $projects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projects->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => $integrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
        ]);

        return $report;
    }

    public function createReportGroupedByClients($clients, $projects, $users, $integrations, array $plans): TableReportData
    {
        $report = new TableReportData();

        foreach ($clients as $client) {
            $group = new TableReportGroupData();
            $group->groupLabel = $client->name;

            $rows = new Collection();
            $clientProjects = $projects->filter(fn($project) => $project->client_id === $client->id);
            foreach ($clientProjects as $project) {
                $row = new TableReportRowData();
                $row->id = $project->id;

                $projectTypeLabel = $project->project_type->label();

                $status = match ($project->is_active) {
                    true => 'active',
                    false => 'inactive'
                };

                $team = $this->resolveProjectTeam($clients, $users, $project);
                $client = $team['client'];
                $manager = $team['manager'];
                $specialist = $team['specialist'];

                $kpi = $project->kpi?->label() ?? '—';

                $projectIntegrations = $integrations->get($project->id, []);

                $plan = isset($plans[$project->id]) ? $plans[$project->id] : null;

                $row->data = new Collection(array_merge(
                    $this->createClientData($projectTypeLabel, $client?->name ?? '—', $project->name, $project->id, $status),
                    $this->createTeamData($team['managerName'], $manager?->id, $team['specialistName'], $specialist?->id),
                    $this->createFinancialData($kpi, $plan, $project->bonusCondition?->client_payment, 0, 0),
                    $this->createSpendingsData(null, null, null, []),
                    $this->createIntegrationData($projectIntegrations)
                ));

                $rows->push($row);
            }
            $group->rows = $rows;

            $clientIntegrations = $integrations->filter(function ($integrations, $projectId) use ($clientProjects) {
                return $clientProjects->pluck('id')->contains($projectId);
            });

            $group->summary = new Collection([
                'client' => [
                    'count' => $clientProjects->pluck('client_id')->unique()->count()
                ],
                'client-project' => [
                    'count' => $clientProjects->count()
                ],
                'status' => [
                    'active' => $clientProjects->filter(fn($project) => $project->is_active)->count(),
                    'inactive' => $clientProjects->filter(fn($project) => !$project->is_active)->count()
                ],
                'tool' => $clientIntegrations->flatten()
                    ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
            ]);

            $report->groups->push($group);
        }

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $projects->count()
            ],
            'status' => [
                'active' => $projects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projects->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => $integrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
        ]);

        return $report;
    }

    public function createReportGroupedByTools($clients, $projects, $users, $integrations, array $plans): TableReportData
    {
        $report = new TableReportData();

        $integrationsGroupList = $integrations->flatten()->unique('integration.code');

        foreach ($integrationsGroupList as $integrationGroup) {
            $group = new TableReportGroupData();
            $group->groupLabel = $integrationGroup->integration->name;

            $rows = new Collection();
            $projectIds = $integrations
                ->filter(
                    fn($integrationsByProject) => $integrationsByProject->contains(
                        fn($integration) => $integration->integration->code === $integrationGroup->integration->code
                    )
                )
                ->keys();

            $projectsByIntegration = $projects->filter(fn($project) => $projectIds->contains($project->id));

            foreach ($projectsByIntegration as $project) {
                $row = new TableReportRowData();
                $row->id = $project->id;

                $projectTypeLabel = $project->project_type->label();

                $status = match ($project->is_active) {
                    true => 'active',
                    false => 'inactive'
                };

                $team = $this->resolveProjectTeam($clients, $users, $project);
                $client = $team['client'];
                $manager = $team['manager'];
                $specialist = $team['specialist'];

                $kpi = $project->kpi?->label() ?? '—';

                $projectIntegrations = $integrations->get($project->id, []);

                $plan = isset($plans[$project->id]) ? $plans[$project->id] : null;

                $row->data = new Collection(array_merge(
                    $this->createClientData($projectTypeLabel, $client?->name ?? '—', $project->name, $project->id, $status),
                    $this->createTeamData($team['managerName'], $manager?->id, $team['specialistName'], $specialist?->id),
                    $this->createFinancialData($kpi, $plan, $project->bonusCondition?->client_payment, 0, 0),
                    $this->createSpendingsData(null, null, null, []),
                    $this->createIntegrationData($projectIntegrations)
                ));

                $rows->push($row);
            }

            $group->rows = $rows;

            $group->summary = new Collection([
                'client' => [
                    'count' => $projectsByIntegration->pluck('client_id')->unique()->count()
                ],
                'client-project' => [
                    'count' => $projectsByIntegration->count()
                ],
                'status' => [
                    'active' => $projectsByIntegration->filter(fn($project) => $project->is_active)->count(),
                    'inactive' => $projectsByIntegration->filter(fn($project) => !$project->is_active)->count()
                ],
                'tool' => [
                    $this->getIntegrationLogoComponent($integrationGroup->integration->code) => $projectsByIntegration->count()
                ]
            ]);

            $report->groups->push($group);
        }

        // Группа с проектами без интеграций
        $projectsWithoutIntegration = $projects->filter(fn($project) => !$integrations->keys()->contains($project->id));
        $group = new TableReportGroupData();
        $group->groupLabel = 'Без настроенных инструментов';

        $rows = new Collection();

        foreach ($projectsWithoutIntegration as $project) {
            $row = new TableReportRowData();
            $row->id = $project->id;

            $projectTypeLabel = $project->project_type->label();

            $status = match ($project->is_active) {
                true => 'active',
                false => 'inactive'
            };

            $team = $this->resolveProjectTeam($clients, $users, $project);
            $client = $team['client'];
            $manager = $team['manager'];
            $specialist = $team['specialist'];

            $kpi = $project->kpi?->label() ?? '—';

            $projectIntegrations = $integrations->get($project->id, []);

            $plan = isset($plans[$project->id]) ? $plans[$project->id] : null;

            $row->data = new Collection(array_merge(
                $this->createClientData($projectTypeLabel, $client?->name ?? '—', $project->name, $project->id, $status),
                $this->createTeamData($team['managerName'], $manager?->id, $team['specialistName'], $specialist?->id),
                $this->createFinancialData($kpi, $plan, $project->bonusCondition?->client_payment, 0, 0),
                $this->createSpendingsData(null, null, null, []),
                $this->createIntegrationData($projectIntegrations)
            ));

            $rows->push($row);
        }

        $group->rows = $rows;

        $group->summary = new Collection([
            'client' => [
                'count' => $projectsWithoutIntegration->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $projectsWithoutIntegration->count()
            ],
            'status' => [
                'active' => $projectsWithoutIntegration->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projectsWithoutIntegration->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => []
        ]);

        $report->groups->push($group);

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $projects->count()
            ],
            'status' => [
                'active' => $projects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projects->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => $integrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
        ]);

        return $report;
    }

    public function createReportGroupedByRoles($clients, $projects, $users, $integrations, array $plans): TableReportData
    {
        $report = new TableReportData();

        $roles = $users->pluck('roles')->flatten()->unique('id');

        foreach ($roles as $role) {
            $group = new TableReportGroupData();
            $group->groupLabel = $role->display_name;

            $rows = new Collection();

            $projectsWithRole = $projects->filter(function ($project) use ($clients, $users, $role) {
                $team = $this->resolveProjectTeam($clients, $users, $project);
                $manager = $team['manager'];
                $specialist = $team['specialist'];

                return ($manager && $manager->roles->contains($role))
                    || ($specialist && $specialist->roles->contains($role));
            });

            foreach ($projectsWithRole as $project) {
                $row = new TableReportRowData();
                $row->id = $project->id;

                $projectTypeLabel = $project->project_type->label();

                $status = match ($project->is_active) {
                    true => 'active',
                    false => 'inactive'
                };

                $team = $this->resolveProjectTeam($clients, $users, $project);
                $client = $team['client'];
                $manager = $team['manager'];
                $specialist = $team['specialist'];

                $kpi = $project->kpi?->label() ?? '—';

                $projectIntegrations = $integrations->get($project->id, []);

                $plan = isset($plans[$project->id]) ? $plans[$project->id] : null;

                $row->data = new Collection(array_merge(
                    $this->createClientData($projectTypeLabel, $client?->name ?? '—', $project->name, $project->id, $status),
                    $this->createTeamData($team['managerName'], $manager?->id, $team['specialistName'], $specialist?->id),
                    $this->createFinancialData($kpi, $plan, $project->bonusCondition?->client_payment, 0, 0),
                    $this->createSpendingsData(null, null, null, []),
                    $this->createIntegrationData($projectIntegrations)
                ));

                $rows->push($row);
            }

            if ($rows->isNotEmpty()) {
                $group->rows = $rows;

                $roleIntegrations = $integrations->filter(function ($integrations, $projectId) use ($projectsWithRole) {
                    return $projectsWithRole->pluck('id')->contains($projectId);
                });

                $group->summary = new Collection([
                    'client' => [
                        'count' => $projectsWithRole->pluck('client_id')->unique()->count()
                    ],
                    'client-project' => [
                        'count' => $projectsWithRole->count()
                    ],
                    'status' => [
                        'active' => $projectsWithRole->filter(fn($project) => $project->is_active)->count(),
                        'inactive' => $projectsWithRole->filter(fn($project) => !$project->is_active)->count()
                    ],
                    'tool' => $roleIntegrations->flatten()
                        ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))

                ]);

                $report->groups->push($group);
            }
        }

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client-project' => [
                'count' => $projects->count()
            ],
            'status' => [
                'active' => $projects->filter(fn($project) => $project->is_active)->count(),
                'inactive' => $projects->filter(fn($project) => !$project->is_active)->count()
            ],
            'tool' => $integrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code))
        ]);

        return $report;
    }

    public function createClientData(string $projectTypeLabel, string $clientName, string $projectName, int $projectId, string $status): array
    {
        return [
            'project-type' => ['name' => $projectTypeLabel],
            'client' => ['name' => $clientName],
            'client-project' => [
                'name' => $projectName,
                'id' => $projectId
            ],
            'client-project-id' => ['id' => $projectId],
            'status' => $status
        ];
    }

    public function createTeamData(string $managerName, ?int $managerId, string $specialistName, ?int $specialistId): array
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

    public function createFinancialData(string $kpi, int|string|null $plan, ?int $clientReceipt, ?int $maxBonuses, ?int $acts): array
    {
        return [
            'kpi' => $kpi,
            'plan' => $plan,
            'client-receipt' => $clientReceipt,
            'max-bonuses' => $maxBonuses,
            'acts' => $acts
        ];
    }

    public function createSpendingsData(?array $programming, ?array $copyrighting, ?int $seoLinksSum, ?array $positions): array
    {
        $spendings = [
            'programming' => $programming,
            'copyrighting' => $copyrighting,
            'seo-links' => ['sum' => $seoLinksSum]
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

        $spendings['summary-spendings'] = ['sum' => $totalSum];

        return $spendings;
    }

    public function createIntegrationData(array|Collection $integrations): array
    {
        if (is_array($integrations)) {
            $integrations = collect($integrations);
        }

        // ключ tool - идентификатор столбца, в котором будут рендериться данные
        $initialColumnsData = [
            'tool' => [],
            'login' => null
        ];

        $columnsData = $integrations->reduce(function ($carry, $integration) {
            $integrationCode = $integration->integration->code;

            // Сделана проверка на совпадение с кодом интеграции потому что в отчете некоторые интеграции могут быть сгруппированы
            // под одним значком. Например может быть настроено 3 разные интеграции с 1С, но на фронте они будут объединены в 1.
            // Вложенные ключи используются для рендеринга соответствующей иконки.
            $logoComponent = $this->getIntegrationLogoComponent($integrationCode);
            if (isset($carry['tool'][$logoComponent])) {
                $carry['tool'][$logoComponent] += 1;
            } else {
                $carry['tool'][$logoComponent] = 1;
            }

            if ($integrationCode === 'yandex_direct') {
                $carry['login'] = $integration->settings['client_login']
                    ?? $integration->settings['clientLogin']
                    ?? null;
            }

            return $carry;
        }, $initialColumnsData);

        return $columnsData;
    }

    // TODO: вынести в отдельный класс, например IntegrationLogoMapper
    private function getIntegrationLogoComponent(string $code): string
    {
        // ? Возможно стоит использовать enum?
        return match ($code) {
            'yandex_direct' => 'yandex-direct',
            'callibri' => 'callibri',
            'yandex_search_api' => 'yandex-search-api',
            default => 'default'
        };
    }
}
