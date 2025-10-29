<?php

namespace App\Domain\Statistics\Services;

use App\Data\Statistics\StatisticsReportQueryData;
use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use App\Enums\ChannelReportGrouping;
use App\Enums\Kpi;
use App\Enums\ProjectType;
use App\Repositories\ClientRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;

class StatisticsService
{
    private ClientRepository $clientRepository;
    private ProjectRepository $projectRepository;
    private UserRepository $userRepository;
    private IntegrationRepository $integrationRepository;

    public function __construct(
        ProjectRepository $projectRepository,
        ClientRepository $clientRepository,
        UserRepository $userRepository,
        IntegrationRepository $integrationRepository
    ) {
        $this->projectRepository = $projectRepository;
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
        $this->integrationRepository = $integrationRepository;
    }

    public function getReportData(StatisticsReportQueryData $query): TableReportData
    {
        $clients = $this->clientRepository->all();
        $projects = $this->projectRepository->all();
        $users = $this->userRepository->all();
        $integrations = $this->integrationRepository->getActiveIntegrationsMappedByProjects($projects->pluck('id'));

        if (!$query->showInactive) {
            $projects = $projects->filter(fn($project) => $project->is_active);
        }


        // TODO: разнести логику по соответствующим классам
        if ($query->grouping === ChannelReportGrouping::PROJECT_TYPE) {
            return $this->createReportGroupedByProjectType($clients, $projects, $users, $integrations);
        }

        // if ($query->grouping === ChannelReportGrouping::CLIENTS) {
        //     return $this->createReportGroupedByClients($clients, $projects, $users, $integrations);
        // }

        // if ($query->grouping === ChannelReportGrouping::TOOLS) {
        //     return $this->createReportGroupedByTools($clients, $projects, $users, $integrations);
        // }

        // if ($query->grouping === ChannelReportGrouping::ROLE) {
        //     return $this->createReportGroupedByRoles($clients, $projects, $users, $integrations);
        // }

        return $this->createFlatReport($clients, $projects, $users, $integrations);
    }

    private function createFlatReport(Collection $clients, Collection $projects, Collection $users, Collection $integrations): TableReportData
    {
        $report = new TableReportData();

        $group = new TableReportGroupData();

        $rows = new Collection();

        foreach ($projects as $project) {
            $row = new TableReportRowData();

            $department = match ($project->project_type) {
                ProjectType::CONTEXT_AD => 'Контекст',
                ProjectType::SEO_PROMOTION => 'SEO'
            };

            $client = $clients->firstWhere('id', $project->client_id);

            $manager = $users->firstWhere('id', $client->manager_id);
            $managerName = $manager->first_name . ' ' . mb_substr($manager->last_name, 0, 1) . '.';

            $projectIntegrations = $integrations->get($project->id, new Collection());

            $row->data = new Collection(array_merge(
                [
                    'manager' => [
                        'id' => $manager->id,
                        'name' => $managerName
                    ],
                    'client' => [
                        'name' => $client->name
                    ],
                    'client_project' => [
                        'id' => $project->id,
                        'name' => $project->name
                    ],
                    'client_project_id' => [
                        'id' => $project->id
                    ],
                    'department' => [
                        'name' => $department
                    ],
                    'kpi' => $project->kpi->label(),
                    'parameter' => $this->createParameterData($project->project_type, $project->kpi),
                    'plan' => $this->createPlanData($project->project_type, $project->kpi),
                    'summary' => [],
                    'perdiction' => [],
                    'bonuses' => 0
                ],
                $this->createIntegrationData($projectIntegrations)
            ));
            $rows->push($row);
        }

        $group->rows = $rows;
        $report->groups->push($group);

        $report->summary = new Collection([
            'client' => [
                'count' => $projects->pluck('client_id')->unique()->count()
            ],
            'client_project' => [
                'count' => $projects->count()
            ],
            'service' => $integrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code)),
            'department' => [
                ProjectType::CONTEXT_AD->value => $projects->filter(fn($project) => $project->project_type === ProjectType::CONTEXT_AD)->count(),
                ProjectType::SEO_PROMOTION->value => $projects->filter(fn($project) => $project->project_type === ProjectType::SEO_PROMOTION)->count()
            ]
        ]);

        return $report;
    }

    private function createReportGroupedByProjectType(Collection $clients, Collection $projects, Collection $users, Collection $integrations): TableReportData
    {
        $report = new TableReportData();
        $seoGroup = new TableReportGroupData();
        $seoGroup->groupLabel = 'SEO';
        $contextGroup = new TableReportGroupData();
        $contextGroup->groupLabel = 'Контекст';

        $seoRows = new Collection();
        $contextRows = new Collection();

        foreach ($projects as $project) {
            $row = new TableReportRowData();
            $row->id = $project->id;

            $department = match ($project->project_type) {
                ProjectType::CONTEXT_AD => 'Контекст',
                ProjectType::SEO_PROMOTION => 'SEO'
            };

            $client = $clients->firstWhere('id', $project->client_id);

            $manager = $users->firstWhere('id', $client->manager_id);
            $managerName = $manager->first_name . ' ' . mb_substr($manager->last_name, 0, 1) . '.';

            $projectIntegrations = $integrations->get($project->id, new Collection());

            $row->data = new Collection(array_merge(
                [
                    'manager' => [
                        'id' => $manager->id,
                        'name' => $managerName
                    ],
                    'client' => [
                        'name' => $client->name
                    ],
                    'client_project' => [
                        'id' => $project->id,
                        'name' => $project->name
                    ],
                    'client_project_id' => [
                        'id' => $project->id
                    ],
                    'department' => [
                        'name' => $department
                    ],
                    'kpi' => $project->kpi->label(),
                    'parameter' => $this->createParameterData($project->project_type, $project->kpi),
                    'plan' => $this->createPlanData($project->project_type, $project->kpi),
                    'summary' => [],
                    'perdiction' => [],
                    'bonuses' => 0
                ],
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
            'client_project' => [
                'count' => $seoProjects->count()
            ],
            'service' => $seoIntegrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code)),
            'department' => [
                ProjectType::CONTEXT_AD->value => $seoProjects->filter(fn($project) => $project->project_type === ProjectType::CONTEXT_AD)->count(),
                ProjectType::SEO_PROMOTION->value => $seoProjects->filter(fn($project) => $project->project_type === ProjectType::SEO_PROMOTION)->count()
            ]
        ]);

        $contextGroup->summary = new Collection([
            'client' => [
                'count' => $contextProjects->pluck('client_id')->unique()->count()
            ],
            'client_project' => [
                'count' => $contextProjects->count()
            ],
            'service' => $contextIntegrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code)),
            'department' => [
                ProjectType::CONTEXT_AD->value => $contextProjects->filter(fn($project) => $project->project_type === ProjectType::CONTEXT_AD)->count(),
                ProjectType::SEO_PROMOTION->value => $contextProjects->filter(fn($project) => $project->project_type === ProjectType::SEO_PROMOTION)->count()
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
            'service' => $integrations->flatten()
                ->countBy(fn($integration) => $this->getIntegrationLogoComponent($integration->integration->code)),
            'department' => [
                ProjectType::CONTEXT_AD->value => $projects->filter(fn($project) => $project->project_type === ProjectType::CONTEXT_AD)->count(),
                ProjectType::SEO_PROMOTION->value => $projects->filter(fn($project) => $project->project_type === ProjectType::SEO_PROMOTION)->count()
            ]
        ]);

        return $report;
    }

    private function createParameterData(ProjectType $projectType, Kpi $kpi): array
    {
        return match ($projectType) {
            ProjectType::CONTEXT_AD => match ($kpi) {
                Kpi::TRAFFIC => [
                    ['name' => 'CPC', 'highlight' => false],
                    ['name' => 'Бюджет', 'highlight' => false],
                    ['name' => 'Объём визитов', 'highlight' => true]
                ],
                Kpi::LEADS => [
                    ['name' => 'CPL', 'highlight' => false],
                    ['name' => 'Рекламный бюджет', 'highlight' => false],
                    ['name' => 'Лидов', 'highlight' => true]
                ],
            },
            ProjectType::SEO_PROMOTION => match ($kpi) {
                Kpi::TRAFFIC => [
                    ['name' => 'Объём визитов', 'highlight' => true],
                    ['name' => 'Конверсии', 'highlight' => false]
                ],
                Kpi::POSITIONS => [
                    ['name' => '% позиций в топ 10', 'highlight' => false],
                    ['name' => 'Конверсии', 'highlight' => false]
                ]
            }
        };
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
                    ['value' => 1670, 'format' => null]
                ],
                Kpi::LEADS => [
                    ['value' => 3392, 'format' => 'currency'],
                    ['value' => 190000, 'format' => 'currency'],
                    ['value' => 56, 'format' => null]
                ],
            },
            ProjectType::SEO_PROMOTION => match ($kpi) {
                Kpi::TRAFFIC => [
                    ['value' => 5130, 'format' => null],
                    ['value' => null, 'format' => null]
                ],
                Kpi::POSITIONS => [
                    ['value' => 50, 'format' => 'percent'],
                    ['value' => null, 'format' => null]
                ]
            }
        };
    }

    private function createSummaryData(ProjectType $projectType, Kpi $kpi): array
    {
        return match ($projectType) {
            ProjectType::CONTEXT_AD => match ($kpi) {
                Kpi::TRAFFIC => [
                    ['value' => 45, 'format' => 'currency'],
                    ['value' => 90000, 'format' => 'currency'],
                    ['value' => 1670, 'format' => null]
                ],
                Kpi::LEADS => [
                    ['value' => 3392, 'format' => 'currency'],
                    ['value' => 190000, 'format' => 'currency'],
                    ['value' => 56, 'format' => null]
                ],
            },
            ProjectType::SEO_PROMOTION => match ($kpi) {
                Kpi::TRAFFIC => [
                    ['value' => 5130, 'format' => null],
                    ['value' => null, 'format' => null]
                ],
                Kpi::POSITIONS => [
                    ['value' => 50, 'format' => 'percent'],
                    ['value' => null, 'format' => null]
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
            'login' => null
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
                $carry['login'] = $integration->settings['clientLogin'] ?? null;
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
            default => 'default'
        };
    }
}
