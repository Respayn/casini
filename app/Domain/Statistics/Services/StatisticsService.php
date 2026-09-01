<?php

namespace App\Domain\Statistics\Services;

use App\Data\Statistics\StatisticsReportQueryData;
use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use App\Domain\Statistics\Enums\StatisticsReportDetailLevel;
use App\Enums\ChannelReportGrouping;
use App\Helpers\DateTimeHelper;
use App\Models\CallibriDailyLeadCount;
use App\Models\YandexDirectDailySpending;
use App\Models\YandexSearchApiDailyTopPercent;
use App\Repositories\ClientRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Src\Planning\Application\ProjectPlanService;

class StatisticsService
{
    private ClientRepository $clientRepository;

    private ProjectRepository $projectRepository;

    private UserRepository $userRepository;

    private IntegrationRepository $integrationRepository;

    private ProjectPlanService $projectPlanService;

    private StatisticsClosingColumnsCalculator $closingColumnsCalculator;

    private StatisticsMonthlyBonusSnapshotService $bonusSnapshotService;

    private StatisticsClosingColumnsAggregator $closingColumnsAggregator;

    public function __construct(
        ProjectRepository $projectRepository,
        ClientRepository $clientRepository,
        UserRepository $userRepository,
        IntegrationRepository $integrationRepository,
        ProjectPlanService $projectPlanService,
        StatisticsClosingColumnsCalculator $closingColumnsCalculator,
        StatisticsMonthlyBonusSnapshotService $bonusSnapshotService,
        StatisticsClosingColumnsAggregator $closingColumnsAggregator,
    ) {
        $this->projectRepository = $projectRepository;
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
        $this->integrationRepository = $integrationRepository;
        $this->projectPlanService = $projectPlanService;
        $this->closingColumnsCalculator = $closingColumnsCalculator;
        $this->bonusSnapshotService = $bonusSnapshotService;
        $this->closingColumnsAggregator = $closingColumnsAggregator;
    }

    public function getUserSettings(int $userId): StatisticsReportQueryData
    {
        $savedSettings = DB::table('statistics_report_user_settings')
            ->where('user_id', $userId)
            ->value('settings');

        if ($savedSettings) {
            return StatisticsReportQueryData::hydrateFromSavedSettings($savedSettings);
        }

        return StatisticsReportQueryData::create();
    }

    public function saveUserSettings(int $userId, StatisticsReportQueryData $settings): void
    {
        DB::table('statistics_report_user_settings')
            ->updateOrInsert(
                ['user_id' => $userId],
                ['settings' => $settings->toJson()]
            );
    }

    public function getReportData(StatisticsReportQueryData $query, ?int $projectId = null): TableReportData
    {
        $user = Auth::user();

        $clients = $this->clientRepository->all();
        if ($user->isManager() && ! $user->hasAnyPermission(['read statistics', 'all statistics'])) {
            $clients = $clients->filter(fn ($client) => $client->manager_id === $user->id);
        }

        $projects = $this->projectRepository->all();
        $projects = $projects->filter(fn ($project) => $clients->pluck('id')->contains($project->client_id));
        if ($user->isSpecialist() && ! $user->hasAnyPermission(['read statistics', 'full statistics'])) {
            $projects = $projects->filter(fn ($project) => $project->specialist_id === $user->id);
        }

        $users = $this->userRepository->all();
        $integrations = $this->integrationRepository->getActiveIntegrationsMappedByProjects($projects->pluck('id'));

        if ($projectId !== null) {
            $projects = $projects->filter(fn ($project) => $project->id === $projectId);
            $clientIds = $projects->pluck('client_id');
            $clients = $clients->filter(fn ($client) => $clientIds->contains($client->id));
        } elseif (! $query->showInactive) {
            $projects = $projects->filter(fn ($project) => $project->is_active);
        }

        $plans = $query->isSingleMonthPeriod()
            ? $this->projectPlanService->getMonthlyPlansForStatistics($query->dateFrom->year, $query->dateFrom->month)
            : [];

        $gridMonth = $query->detailGridMonth();
        $spendFrom = $query->detailLevel === StatisticsReportDetailLevel::BY_MONTH
            ? $query->dateFrom->copy()->startOfMonth()->startOfDay()
            : $gridMonth->copy()->startOfMonth()->startOfDay();
        $spendTo = $query->detailLevel === StatisticsReportDetailLevel::BY_MONTH
            ? $query->dateTo->copy()->endOfMonth()->startOfDay()
            : $gridMonth->copy()->endOfMonth()->startOfDay();
        $spendingsByProject = $this->loadDirectDailySpendings($projects, $spendFrom, $spendTo, $query->includeVat);
        $leadsByProject = $this->loadCallibriLeadCounts($projects, $spendFrom, $spendTo);
        $topPercentsByProject = $this->loadSearchApiDailyTopPercents($projects, $spendFrom, $spendTo);

        // TODO: разнести логику по соответствующим классам
        if ($query->grouping === ChannelReportGrouping::PROJECT_TYPE) {
            return $this->createReportGroupedByProjectType($clients, $projects, $users, $integrations, $query->detailLevel, $gridMonth, $query->dateFrom, $query->dateTo, $plans, $spendingsByProject, $leadsByProject, $topPercentsByProject);
        }

        if ($query->grouping === ChannelReportGrouping::CLIENTS) {
            return $this->createReportGroupedByClients($clients, $projects, $users, $integrations, $query->detailLevel, $gridMonth, $query->dateFrom, $query->dateTo, $plans, $spendingsByProject, $leadsByProject, $topPercentsByProject);
        }

        if ($query->grouping === ChannelReportGrouping::TOOLS) {
            return $this->createReportGroupedByTools($clients, $projects, $users, $integrations, $query->detailLevel, $gridMonth, $query->dateFrom, $query->dateTo, $plans, $spendingsByProject, $leadsByProject, $topPercentsByProject);
        }

        return $this->createFlatReport($clients, $projects, $users, $integrations, $query->detailLevel, $gridMonth, $query->dateFrom, $query->dateTo, $plans, $spendingsByProject, $leadsByProject, $topPercentsByProject);
    }

    private function createFlatReport(
        Collection $clients,
        Collection $projects,
        Collection $users,
        Collection $integrations,
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        Carbon $periodTo,
        array $plans,
        array $spendingsByProject,
        array $leadsByProject,
        array $topPercentsByProject = []
    ): TableReportData {
        $report = new TableReportData;

        $group = new TableReportGroupData;

        $rows = new Collection;

        foreach ($projects as $project) {
            $row = new TableReportRowData;
            $row->id = $project->id;

            $client = $clients->firstWhere('id', $project->client_id);

            $manager = $users->firstWhere('id', $client->manager_id);
            $managerName = $manager->first_name.' '.mb_substr($manager->last_name, 0, 1).'.';

            $projectIntegrations = $integrations->get($project->id, new Collection);

            $plan = $this->resolvePlanCell($plans, $project->id, $project->project_type, $project->kpi);
            $closing = $this->resolveClosingColumns(
                $project,
                $gridMonth,
                $periodFrom,
                $periodTo,
                $plan,
                $spendingsByProject[$project->id] ?? [],
                $leadsByProject[$project->id] ?? [],
                $topPercentsByProject[$project->id] ?? [],
            );

            $row->data = new Collection(array_merge(
                [
                    'manager' => [
                        'id' => $manager->id,
                        'name' => $managerName,
                    ],
                    'client' => [
                        'name' => $client->name,
                    ],
                    'client-project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'client-project-id' => [
                        'id' => $project->id,
                    ],
                    'project-type' => $project->project_type->label(),
                    'kpi' => $project->kpi->label(),
                    'parameter' => $this->projectPlanService->getKpiParametersSchemaForStatistics($project->project_type, $project->kpi),
                    'plan' => $plan,
                    'summary' => $closing['summary'],
                    'prediction' => $closing['prediction'],
                    'bonuses' => $closing['bonuses'],
                ],
                $this->createIntegrationData($projectIntegrations),
                $this->createFactData(
                    $project->project_type,
                    $project->kpi,
                    $detailLevel,
                    $gridMonth,
                    $periodFrom,
                    $periodTo,
                    $spendingsByProject[$project->id] ?? [],
                    $leadsByProject[$project->id] ?? [],
                    $plan,
                    $topPercentsByProject[$project->id] ?? [],
                )
            ));
            $rows->push($row);
        }

        $group->rows = $rows;
        $report->groups->push($group);

        $report->summary = $this->buildReportSummary(
            $projects,
            $integrations,
            $rows,
        );

        return $report;
    }

    private function createReportGroupedByProjectType(
        Collection $clients,
        Collection $projects,
        Collection $users,
        Collection $integrations,
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        Carbon $periodTo,
        array $plans,
        array $spendingsByProject,
        array $leadsByProject,
        array $topPercentsByProject = []
    ): TableReportData {
        $report = new TableReportData;
        $seoGroup = new TableReportGroupData;
        $seoGroup->groupLabel = ProjectType::SEO_PROMOTION->label();
        $contextGroup = new TableReportGroupData;
        $contextGroup->groupLabel = ProjectType::CONTEXT_AD->label();

        $seoRows = new Collection;
        $contextRows = new Collection;

        foreach ($projects as $project) {
            $row = new TableReportRowData;
            $row->id = $project->id;

            $client = $clients->firstWhere('id', $project->client_id);

            $manager = $users->firstWhere('id', $client->manager_id);
            $managerName = $manager->first_name.' '.mb_substr($manager->last_name, 0, 1).'.';

            $projectIntegrations = $integrations->get($project->id, new Collection);

            $plan = $this->resolvePlanCell($plans, $project->id, $project->project_type, $project->kpi);
            $closing = $this->resolveClosingColumns(
                $project,
                $gridMonth,
                $periodFrom,
                $periodTo,
                $plan,
                $spendingsByProject[$project->id] ?? [],
                $leadsByProject[$project->id] ?? [],
                $topPercentsByProject[$project->id] ?? [],
            );

            $row->data = new Collection(array_merge(
                [
                    'manager' => [
                        'id' => $manager->id,
                        'name' => $managerName,
                    ],
                    'client' => [
                        'name' => $client->name,
                    ],
                    'client-project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'client-project-id' => [
                        'id' => $project->id,
                    ],
                    'project-type' => $project->project_type->label(),
                    'kpi' => $project->kpi->label(),
                    'parameter' => $this->projectPlanService->getKpiParametersSchemaForStatistics($project->project_type, $project->kpi),
                    'plan' => $plan,
                    'summary' => $closing['summary'],
                    'prediction' => $closing['prediction'],
                    'bonuses' => $closing['bonuses'],
                ],
                $this->createIntegrationData($projectIntegrations),
                $this->createFactData(
                    $project->project_type,
                    $project->kpi,
                    $detailLevel,
                    $gridMonth,
                    $periodFrom,
                    $periodTo,
                    $spendingsByProject[$project->id] ?? [],
                    $leadsByProject[$project->id] ?? [],
                    $plan,
                    $topPercentsByProject[$project->id] ?? [],
                )
            ));

            if ($project->project_type === ProjectType::SEO_PROMOTION) {
                $seoRows->push($row);
            } else {
                $contextRows->push($row);
            }
        }

        $seoGroup->rows = $seoRows;
        $contextGroup->rows = $contextRows;

        $seoProjects = $projects->filter(fn ($project) => $project->project_type === ProjectType::SEO_PROMOTION);
        $contextProjects = $projects->filter(fn ($project) => $project->project_type === ProjectType::CONTEXT_AD);

        $seoIntegrations = $integrations->filter(function ($integrations, $projectId) use ($seoProjects) {
            return $seoProjects->pluck('id')->contains($projectId);
        });
        $contextIntegrations = $integrations->filter(function ($integrations, $projectId) use ($contextProjects) {
            return $contextProjects->pluck('id')->contains($projectId);
        });

        $seoGroup->summary = $this->buildGroupSummary(
            $seoProjects,
            $seoIntegrations,
            $seoRows,
        );

        $contextGroup->summary = $this->buildGroupSummary(
            $contextProjects,
            $contextIntegrations,
            $contextRows,
        );

        $report->groups = new Collection([$seoGroup, $contextGroup]);

        $report->summary = $this->buildReportSummary(
            $projects,
            $integrations,
            $seoRows->concat($contextRows)->values(),
        );

        return $report;
    }

    public function createReportGroupedByClients(
        Collection $clients,
        Collection $projects,
        Collection $users,
        Collection $integrations,
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        Carbon $periodTo,
        array $plans,
        array $spendingsByProject,
        array $leadsByProject,
        array $topPercentsByProject = []
    ): TableReportData {
        $report = new TableReportData;

        foreach ($clients as $client) {
            $group = new TableReportGroupData;
            $group->groupLabel = $client->name;

            $rows = new Collection;
            $clientProjects = $projects->filter(fn ($project) => $project->client_id === $client->id);
            foreach ($clientProjects as $project) {
                $row = new TableReportRowData;
                $row->id = $project->id;

                $client = $clients->firstWhere('id', $project->client_id);

                $manager = $users->firstWhere('id', $client->manager_id);
                $managerName = $manager->first_name.' '.mb_substr($manager->last_name, 0, 1).'.';

                $projectIntegrations = $integrations->get($project->id, []);

                $plan = $this->resolvePlanCell($plans, $project->id, $project->project_type, $project->kpi);
                $closing = $this->resolveClosingColumns(
                    $project,
                    $gridMonth,
                    $periodFrom,
                    $periodTo,
                    $plan,
                    $spendingsByProject[$project->id] ?? [],
                    $leadsByProject[$project->id] ?? [],
                    $topPercentsByProject[$project->id] ?? [],
                );

                $row->data = new Collection(array_merge(
                    [
                        'manager' => [
                            'id' => $manager->id,
                            'name' => $managerName,
                        ],
                        'client' => [
                            'name' => $client->name,
                        ],
                        'client-project' => [
                            'id' => $project->id,
                            'name' => $project->name,
                        ],
                        'client-project-id' => [
                            'id' => $project->id,
                        ],
                        'project-type' => $project->project_type->label(),
                        'kpi' => $project->kpi->label(),
                        'parameter' => $this->projectPlanService->getKpiParametersSchemaForStatistics($project->project_type, $project->kpi),
                        'plan' => $plan,
                        'summary' => $closing['summary'],
                        'prediction' => $closing['prediction'],
                        'bonuses' => $closing['bonuses'],
                    ],
                    $this->createIntegrationData($projectIntegrations),
                    $this->createFactData(
                        $project->project_type,
                        $project->kpi,
                        $detailLevel,
                        $gridMonth,
                        $periodFrom,
                        $periodTo,
                        $spendingsByProject[$project->id] ?? [],
                        $leadsByProject[$project->id] ?? [],
                        $plan,
                        $topPercentsByProject[$project->id] ?? [],
                    )
                ));

                $rows->push($row);
            }

            $group->rows = $rows;

            $clientIntegrations = $integrations->filter(function ($integrations, $projectId) use ($clientProjects) {
                return $clientProjects->pluck('id')->contains($projectId);
            });

            $group->summary = $this->buildGroupSummary(
                $clientProjects,
                $clientIntegrations,
                $rows,
            );

            $report->groups->push($group);
        }

        $allRows = $report->groups->flatMap(fn ($group) => $group->rows)->values();
        $report->summary = $this->buildReportSummary(
            $projects,
            $integrations,
            $allRows,
        );

        return $report;
    }

    public function createReportGroupedByTools(
        Collection $clients,
        Collection $projects,
        Collection $users,
        Collection $integrations,
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        Carbon $periodTo,
        array $plans,
        array $spendingsByProject,
        array $leadsByProject,
        array $topPercentsByProject = []
    ): TableReportData {
        $report = new TableReportData;

        $integrationsGroupList = $integrations->flatten()->unique('integration.code');

        foreach ($integrationsGroupList as $integrationGroup) {
            $group = new TableReportGroupData;
            $group->groupLabel = $integrationGroup->integration->name;

            $rows = new Collection;
            $projectIds = $integrations
                ->filter(
                    fn ($integrationsByProject) => $integrationsByProject->contains(
                        fn ($integration) => $integration->integration->code === $integrationGroup->integration->code
                    )
                )
                ->keys();

            $projectsByIntegration = $projects->filter(fn ($project) => $projectIds->contains($project->id));

            foreach ($projectsByIntegration as $project) {
                $row = new TableReportRowData;
                $row->id = $project->id;

                $client = $clients->firstWhere('id', $project->client_id);

                $manager = $users->firstWhere('id', $client->manager_id);
                $managerName = $manager->first_name.' '.mb_substr($manager->last_name, 0, 1).'.';

                $projectIntegrations = $integrations->get($project->id, []);

                $plan = $this->resolvePlanCell($plans, $project->id, $project->project_type, $project->kpi);
                $closing = $this->resolveClosingColumns(
                    $project,
                    $gridMonth,
                    $periodFrom,
                    $periodTo,
                    $plan,
                    $spendingsByProject[$project->id] ?? [],
                    $leadsByProject[$project->id] ?? [],
                    $topPercentsByProject[$project->id] ?? [],
                );

                $row->data = new Collection(array_merge(
                    [
                        'manager' => [
                            'id' => $manager->id,
                            'name' => $managerName,
                        ],
                        'client' => [
                            'name' => $client->name,
                        ],
                        'client-project' => [
                            'id' => $project->id,
                            'name' => $project->name,
                        ],
                        'client-project-id' => [
                            'id' => $project->id,
                        ],
                        'project-type' => $project->project_type->label(),
                        'kpi' => $project->kpi->label(),
                        'parameter' => $this->projectPlanService->getKpiParametersSchemaForStatistics($project->project_type, $project->kpi),
                        'plan' => $plan,
                        'summary' => $closing['summary'],
                        'prediction' => $closing['prediction'],
                        'bonuses' => $closing['bonuses'],
                    ],
                    $this->createIntegrationData($projectIntegrations),
                    $this->createFactData(
                        $project->project_type,
                        $project->kpi,
                        $detailLevel,
                        $gridMonth,
                        $periodFrom,
                        $periodTo,
                        $spendingsByProject[$project->id] ?? [],
                        $leadsByProject[$project->id] ?? [],
                        $plan,
                        $topPercentsByProject[$project->id] ?? [],
                    )
                ));

                $rows->push($row);
            }

            $group->rows = $rows;

            $group->summary = (new Collection([
                'client' => [
                    'count' => $projectsByIntegration->pluck('client_id')->unique()->count(),
                ],
                'client-project' => [
                    'count' => $projectsByIntegration->count(),
                ],
                'service' => [$this->getIntegrationLogoComponent($integrationGroup->integration->code) => $projectsByIntegration->count()],
            ]))->merge($this->closingColumnsAggregator->aggregate($rows));

            $report->groups->push($group);
        }

        // Группа с проектами без интеграций
        $projectsWithoutIntegration = $projects->filter(fn ($project) => ! $integrations->keys()->contains($project->id));
        $group = new TableReportGroupData;
        $group->groupLabel = 'Без настроенных инструментов';

        $rows = new Collection;

        foreach ($projectsWithoutIntegration as $project) {
            $row = new TableReportRowData;
            $row->id = $project->id;

            $client = $clients->firstWhere('id', $project->client_id);

            $manager = $users->firstWhere('id', $client->manager_id);
            $managerName = $manager->first_name.' '.mb_substr($manager->last_name, 0, 1).'.';

            $projectIntegrations = $integrations->get($project->id, []);

            $plan = $this->resolvePlanCell($plans, $project->id, $project->project_type, $project->kpi);
            $closing = $this->resolveClosingColumns(
                $project,
                $gridMonth,
                $periodFrom,
                $periodTo,
                $plan,
                $spendingsByProject[$project->id] ?? [],
                $leadsByProject[$project->id] ?? [],
                $topPercentsByProject[$project->id] ?? [],
            );

            $row->data = new Collection(array_merge(
                [
                    'manager' => [
                        'id' => $manager->id,
                        'name' => $managerName,
                    ],
                    'client' => [
                        'name' => $client->name,
                    ],
                    'client-project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'client-project-id' => [
                        'id' => $project->id,
                    ],
                    'project-type' => $project->project_type->label(),
                    'kpi' => $project->kpi->label(),
                    'parameter' => $this->projectPlanService->getKpiParametersSchemaForStatistics($project->project_type, $project->kpi),
                    'plan' => $plan,
                    'summary' => $closing['summary'],
                    'prediction' => $closing['prediction'],
                    'bonuses' => $closing['bonuses'],
                ],
                $this->createIntegrationData($projectIntegrations),
                $this->createFactData(
                    $project->project_type,
                    $project->kpi,
                    $detailLevel,
                    $gridMonth,
                    $periodFrom,
                    $periodTo,
                    $spendingsByProject[$project->id] ?? [],
                    $leadsByProject[$project->id] ?? [],
                    $plan,
                    $topPercentsByProject[$project->id] ?? [],
                )
            ));

            $rows->push($row);
        }

        $group->rows = $rows;

        $group->summary = (new Collection([
            'client' => [
                'count' => $projectsWithoutIntegration->pluck('client_id')->unique()->count(),
            ],
            'client-project' => [
                'count' => $projectsWithoutIntegration->count(),
            ],
            'service' => [],
        ]))->merge($this->closingColumnsAggregator->aggregate($rows));

        $report->groups->push($group);

        $allRows = $report->groups->flatMap(fn ($group) => $group->rows)->values();
        $report->summary = $this->buildReportSummary(
            $projects,
            $integrations,
            $allRows,
        );

        return $report;
    }

    // TODO: скорее всего сюда нужно будет передавать ID проекта или данные, которые будут получены заранее
    // Пока просто описана структура
    private function createPlanData(ProjectType $projectType, Kpi $kpi): array
    {
        return match ($projectType) {
            ProjectType::CONTEXT_AD => match ($kpi) {
                Kpi::TRAFFIC => [
                    ['value' => 45, 'format' => 'currency'],
                    ['value' => 90000, 'format' => 'currency'],
                    ['value' => 1670, 'format' => null],
                ],
                Kpi::LEADS => [
                    ['value' => 3392, 'format' => 'currency'],
                    ['value' => 190000, 'format' => 'currency'],
                    ['value' => 56, 'format' => null],
                ],
            },
            ProjectType::SEO_PROMOTION => match ($kpi) {
                Kpi::TRAFFIC => [
                    ['value' => 5130, 'format' => null],
                    ['value' => null, 'format' => null],
                ],
                Kpi::POSITIONS => [
                    ['value' => 50, 'format' => 'percent'],
                    ['value' => null, 'format' => null],
                ]
            }
        };
    }

    public function createIntegrationData(array|Collection $integrations): array
    {
        if (is_array($integrations)) {
            $integrations = collect($integrations);
        }

        // ключ service - идентификатор столбца, в котором будут рендериться данные
        $initialColumnsData = [
            'service' => [],
            'login' => null,
        ];

        $columnsData = $integrations->reduce(function ($carry, $integration) {
            $integrationCode = $integration->integration->code;

            // Сделана проверка на совпадение с кодом интеграции потому что в отчете некоторые интеграции могут быть сгруппированы
            // под одним значком. Например может быть настроено 3 разные интеграции с 1С, но на фронте они будут объединены в 1.
            // Вложенные ключи используются для рендеринга соответствующей иконки.
            $logoComponent = $this->getIntegrationLogoComponent($integrationCode);
            if (isset($carry['service'][$logoComponent])) {
                $carry['service'][$logoComponent] += 1;
            } else {
                $carry['service'][$logoComponent] = 1;
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
            'yandex_metrika' => 'yandex-metrika',
            'callibri' => 'callibri',
            'yandex_search_api' => 'yandex-search-api',
            'google_sheets' => 'google-sheets',
            default => 'default'
        };
    }

    /**
     * @param  array<string, float>  $spendByDay  ключ Y-m-d => расход
     * @param  array<string, int>  $leadsByDay  ключ Y-m-d => число лидов
     * @param  list<array{value: mixed, format: mixed}>  $planCell  месячный план из resolvePlanCell
     * @param  array<string, float>  $topPercentsByDay  ключ Y-m-d => % в ТОП-10
     * @return array<string, list<array{plan: array{value: mixed, format: mixed}, fact: array{value: mixed, format: mixed}}>>
     */
    private function createFactData(
        ProjectType $projectType,
        Kpi $kpi,
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        Carbon $periodTo,
        array $spendByDay,
        array $leadsByDay = [],
        array $planCell = [],
        array $topPercentsByDay = [],
    ): array {
        $parameters = $this->projectPlanService->getKpiParametersSchemaForStatistics($projectType, $kpi);
        $budgetIndex = $this->resolveAdvertisingBudgetParameterIndex($projectType, $kpi);
        $leadsIndex = $this->resolveLeadsParameterIndex($projectType, $kpi);

        $parameterCodes = $this->projectPlanService->getParameterCodes($projectType, $kpi);
        $divisibleCodes = ['budget', 'visits', 'leads', 'conversions'];

        $buckets = $this->buildFactBuckets($detailLevel, $gridMonth, $periodFrom, $periodTo);
        $daysInMonth = $gridMonth->daysInMonth();
        $result = [];

        foreach ($buckets as $key => [$from, $to]) {
            $budgetFact = $budgetIndex === null
                ? null
                : $this->sumDirectSpendForRange($spendByDay, $from, $to);
            $leadsFact = $leadsIndex === null
                ? null
                : $this->sumLeadCountsForRange($leadsByDay, $from, $to);
            // Факт визитов (Метрика) пока не подключён — CPC будет «-», пока нет источника.
            $visitsFact = null;
            $topPercentFact = $this->averageTopPercentForRange($topPercentsByDay, $from, $to);

            $bucketDays = $from->diffInDays($to) + 1;

            $slots = [];
            foreach (array_values($parameters) as $index => $parameter) {
                $factValue = null;
                $format = null;
                $code = $parameterCodes[$index] ?? null;

                if ($code === 'budget' && $budgetIndex !== null) {
                    $factValue = $budgetFact;
                    $format = 'currency';
                } elseif ($code === 'leads' && $leadsIndex !== null) {
                    $factValue = $leadsFact;
                    $format = null;
                } elseif ($code === 'cpl') {
                    $factValue = $this->divideFact($budgetFact, $leadsFact);
                    $format = 'currency';
                } elseif ($code === 'cpc') {
                    $factValue = $this->divideFact($budgetFact, $visitsFact);
                    $format = 'currency';
                } elseif ($code === 'top_percent') {
                    $factValue = $topPercentFact;
                    $format = 'percent';
                }

                $planSlot = $planCell[$index] ?? ['value' => null, 'format' => null];
                $planValue = $planSlot['value'];
                $isDivisible = $code !== null && in_array($code, $divisibleCodes, true);

                if ($planValue !== null && $isDivisible && $daysInMonth > 0) {
                    if ($detailLevel === StatisticsReportDetailLevel::BY_MONTH) {
                        $proportionalPlan = (int) round((float) $planValue);
                    } else {
                        $proportionalPlan = (int) round((float) $planValue * $bucketDays / $daysInMonth);
                    }
                } elseif ($planValue !== null && ! $isDivisible) {
                    $proportionalPlan = (int) round((float) $planValue);
                } else {
                    $proportionalPlan = null;
                }

                $slots[] = [
                    'plan' => [
                        'value' => $proportionalPlan,
                        'format' => $planSlot['format'],
                    ],
                    'fact' => [
                        'value' => $factValue,
                        'format' => $format,
                    ],
                ];
            }

            $result[$key] = $slots;
        }

        return $result;
    }

    /**
     * Деление фактов (CPL = бюджет/лиды, CPC = бюджет/визиты). Дробное значение допускается.
     */
    private function divideFact(int|float|null $numerator, int|float|null $denominator): ?float
    {
        if ($numerator === null || $denominator === null) {
            return null;
        }

        $denominator = (float) $denominator;
        if ($denominator <= 0.0) {
            return null;
        }

        return round((float) $numerator / $denominator, 2);
    }

    /**
     * @return array<string, array{0: Carbon, 1: Carbon}>
     */
    private function buildFactBuckets(
        StatisticsReportDetailLevel $detailLevel,
        Carbon $gridMonth,
        Carbon $periodFrom,
        Carbon $periodTo,
    ): array {
        if ($detailLevel === StatisticsReportDetailLevel::BY_DAY) {
            $buckets = [];
            $daysCount = $gridMonth->daysInMonth();
            for ($day = 1; $day <= $daysCount; $day++) {
                $date = $gridMonth->copy()->day($day)->startOfDay();
                $buckets['day_'.$day] = [$date->copy(), $date->copy()];
            }

            return $buckets;
        }

        if ($detailLevel === StatisticsReportDetailLevel::BY_WEEK) {
            $buckets = [];
            foreach (DateTimeHelper::getMonthWeekIntervals($gridMonth) as $index => $interval) {
                $buckets['week_'.$index] = [
                    $interval['start']->copy()->startOfDay(),
                    $interval['end']->copy()->startOfDay(),
                ];
            }

            return $buckets;
        }

        $buckets = [];
        $year = (int) $periodFrom->format('Y');
        $month = (int) $periodFrom->format('n');
        $endYear = (int) $periodTo->format('Y');
        $endMonth = (int) $periodTo->format('n');
        $index = 0;

        while ($year < $endYear || ($year === $endYear && $month <= $endMonth)) {
            $monthStart = Carbon::create($year, $month, 1)->startOfDay();
            $buckets['month_'.$index] = [
                $monthStart->copy(),
                $monthStart->copy()->endOfMonth()->startOfDay(),
            ];
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $index++;
        }

        return $buckets;
    }

    private function resolveAdvertisingBudgetParameterIndex(ProjectType $projectType, Kpi $kpi): ?int
    {
        if ($projectType !== ProjectType::CONTEXT_AD) {
            return null;
        }

        if (! in_array($kpi, [Kpi::TRAFFIC, Kpi::LEADS], true)) {
            return null;
        }

        // CONTEXT_AD: cpc/cpl (0), budget (1), visits/leads (2)
        return 1;
    }

    private function resolveLeadsParameterIndex(ProjectType $projectType, Kpi $kpi): ?int
    {
        if ($projectType !== ProjectType::CONTEXT_AD) {
            return null;
        }

        if ($kpi !== Kpi::LEADS) {
            return null;
        }

        // CONTEXT_AD + LEADS: cpl (0), budget (1), leads (2)
        return 2;
    }

    /**
     * @param  array<string, int>  $leadsByDay
     */
    private function sumLeadCountsForRange(array $leadsByDay, Carbon $from, Carbon $to): ?int
    {
        if ($leadsByDay === []) {
            return null;
        }

        $total = 0;
        $hasData = false;

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            if (! array_key_exists($key, $leadsByDay)) {
                continue;
            }

            $hasData = true;
            $total += (int) $leadsByDay[$key];
        }

        return $hasData ? $total : null;
    }

    /**
     * @param  array<string, float>  $spendByDay
     */
    private function sumDirectSpendForRange(array $spendByDay, Carbon $from, Carbon $to): ?float
    {
        if ($spendByDay === []) {
            return null;
        }

        $total = 0.0;
        $hasData = false;

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            if (! array_key_exists($key, $spendByDay)) {
                continue;
            }

            $hasData = true;
            $total += (float) $spendByDay[$key];
        }

        return $hasData ? round($total, 2) : null;
    }

    /**
     * @param  Collection<int, mixed>  $projects
     * @return array<int, array<string, float>>
     */
    private function loadDirectDailySpendings(
        Collection $projects,
        Carbon $from,
        Carbon $to,
        bool $includeVat
    ): array {
        $projectIds = $projects->pluck('id')->filter()->values()->all();
        if ($projectIds === []) {
            return [];
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $today = Carbon::today();
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            return [];
        }

        $column = $includeVat ? 'cost_with_vat' : 'cost_without_vat';

        $rows = YandexDirectDailySpending::query()
            ->whereIn('project_id', $projectIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['project_id', 'date', $column]);

        $map = [];
        foreach ($rows as $row) {
            $dateKey = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : Carbon::parse((string) $row->date)->toDateString();
            $map[(int) $row->project_id][$dateKey] = (float) $row->{$column};
        }

        return $map;
    }

    /**
     * @param  Collection<int, mixed>  $projects
     * @return array<int, array<string, int>>
     */
    private function loadCallibriLeadCounts(
        Collection $projects,
        Carbon $from,
        Carbon $to,
    ): array {
        $projectIds = $projects->pluck('id')->filter()->values()->all();
        if ($projectIds === []) {
            return [];
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $today = Carbon::today();
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            return [];
        }

        $rows = CallibriDailyLeadCount::query()
            ->whereIn('project_id', $projectIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['project_id', 'date', 'leads_count']);

        $map = [];
        foreach ($rows as $row) {
            $dateKey = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : Carbon::parse((string) $row->date)->toDateString();
            $map[(int) $row->project_id][$dateKey] = (int) $row->leads_count;
        }

        return $map;
    }

    /**
     * @param  Collection<int, mixed>  $projects
     * @return array<int, array<string, float>>
     */
    private function loadSearchApiDailyTopPercents(
        Collection $projects,
        Carbon $from,
        Carbon $to,
    ): array {
        $projectIds = $projects->pluck('id')->filter()->values()->all();
        if ($projectIds === []) {
            return [];
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $today = Carbon::today();
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            return [];
        }

        $rows = YandexSearchApiDailyTopPercent::query()
            ->whereIn('project_id', $projectIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['project_id', 'date', 'top_10_percent']);

        $map = [];
        foreach ($rows as $row) {
            $dateKey = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : Carbon::parse((string) $row->date)->toDateString();
            $map[(int) $row->project_id][$dateKey] = (float) $row->top_10_percent;
        }

        return $map;
    }

    /**
     * Среднее % в ТОП-10 по дням бакета, у которых есть снимок.
     *
     * @param  array<string, float>  $topPercentsByDay
     */
    private function averageTopPercentForRange(array $topPercentsByDay, Carbon $from, Carbon $to): ?float
    {
        if ($topPercentsByDay === []) {
            return null;
        }

        $values = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            if (! array_key_exists($key, $topPercentsByDay)) {
                continue;
            }
            $values[] = (float) $topPercentsByDay[$key];
        }

        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 1);
    }

    /**
     * @param  Collection<int, mixed>  $projects
     * @param  Collection<int|string, mixed>  $integrations
     * @param  Collection<int, TableReportRowData>  $rows
     */
    private function buildGroupSummary(Collection $projects, Collection $integrations, Collection $rows): Collection
    {
        return (new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count(),
            ],
            'client-project' => [
                'count' => $projects->count(),
            ],
            'service' => $integrations->flatten()
                ->countBy(fn ($integration) => $this->getIntegrationLogoComponent($integration->integration->code)),
        ]))->merge($this->closingColumnsAggregator->aggregate($rows));
    }

    /**
     * @param  Collection<int, mixed>  $projects
     * @param  Collection<int|string, mixed>  $integrations
     * @param  Collection<int, TableReportRowData>  $rows
     */
    private function buildReportSummary(Collection $projects, Collection $integrations, Collection $rows): Collection
    {
        return (new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count(),
            ],
            'client-project' => [
                'count' => $projects->count(),
            ],
            'service' => $integrations->flatten()
                ->countBy(fn ($integration) => $this->getIntegrationLogoComponent($integration->integration->code)),
        ]))->merge($this->closingColumnsAggregator->aggregate($rows));
    }

    /**
     * Принудительно пересчитать и сохранить снимки бонусов за месяц (кнопка «Обновить данные»).
     *
     * @param  list<int>  $projectIds
     */
    public function recalculateMonthlyBonuses(
        array $projectIds,
        Carbon $month,
        bool $includeVat = false,
    ): void {
        if ($projectIds === []) {
            return;
        }

        $month = $month->copy()->startOfMonth()->startOfDay();
        $projects = $this->projectRepository->all()
            ->filter(fn ($project) => in_array((int) $project->id, $projectIds, true))
            ->values();

        if ($projects->isEmpty()) {
            return;
        }

        $plans = $this->projectPlanService->getMonthlyPlansForStatistics(
            (int) $month->format('Y'),
            (int) $month->format('n'),
        );
        $spendFrom = $month->copy()->startOfMonth()->startOfDay();
        $spendTo = $month->copy()->endOfMonth()->startOfDay();
        $spendingsByProject = $this->loadDirectDailySpendings($projects, $spendFrom, $spendTo, $includeVat);
        $leadsByProject = $this->loadCallibriLeadCounts($projects, $spendFrom, $spendTo);
        $topPercentsByProject = $this->loadSearchApiDailyTopPercents($projects, $spendFrom, $spendTo);

        foreach ($projects as $project) {
            $plan = $this->resolvePlanCell($plans, (int) $project->id, $project->project_type, $project->kpi);
            $closing = $this->closingColumnsCalculator->calculate(
                $project,
                $month,
                $plan,
                $spendingsByProject[$project->id] ?? [],
                $leadsByProject[$project->id] ?? [],
                $topPercentsByProject[$project->id] ?? [],
            );
            $this->bonusSnapshotService->resolve(
                (int) $project->id,
                $month,
                $closing['bonuses'],
                forceRecalculate: true,
            );
        }
    }

    /**
     * @param  list<array{value: mixed, format: mixed}>  $plan
     * @param  array<string, float>  $spendByDay
     * @param  array<string, int>  $leadsByDay
     * @param  array<string, float>  $topPercentsByDay
     * @return array{summary: array, prediction: array, bonuses: array|null}
     */
    private function resolveClosingColumns(
        mixed $project,
        Carbon $gridMonth,
        Carbon $periodFrom,
        Carbon $periodTo,
        array $plan,
        array $spendByDay,
        array $leadsByDay,
        array $topPercentsByDay,
    ): array {
        $isSingleMonth = $periodFrom->year === $periodTo->year
            && $periodFrom->month === $periodTo->month;

        if (! $isSingleMonth) {
            return [
                'summary' => [],
                'prediction' => [],
                'bonuses' => ['kind' => 'dash'],
            ];
        }

        $month = $gridMonth->copy()->startOfMonth()->startOfDay();
        $closing = $this->closingColumnsCalculator->calculate(
            $project,
            $month,
            $plan,
            $spendByDay,
            $leadsByDay,
            $topPercentsByDay,
        );

        $closing['bonuses'] = $this->bonusSnapshotService->resolve(
            (int) $project->id,
            $month,
            $closing['bonuses'],
            forceRecalculate: false,
        );

        return $closing;
    }

    /**
     * Ячейка «План»: массив слотов {value, format}. При отсутствии плана — плейсхолдеры для «-».
     *
     * @param  array<int|string, mixed>  $plans
     * @return list<array{value: mixed, format: mixed}>
     */
    private function resolvePlanCell(array $plans, int $projectId, ProjectType $projectType, Kpi $kpi): array
    {
        if (isset($plans[$projectId]) && is_array($plans[$projectId])) {
            return $plans[$projectId];
        }

        $schema = $this->projectPlanService->getKpiParametersSchemaForStatistics($projectType, $kpi);

        return array_map(
            static fn () => ['value' => null, 'format' => null],
            $schema
        );
    }
}
