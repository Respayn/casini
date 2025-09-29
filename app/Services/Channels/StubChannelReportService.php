<?php

namespace App\Services\Channels;

use App\Contracts\ChannelReportServiceInterface;
use App\Data\Channels\ChannelReportQueryData;
use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use Illuminate\Support\Collection;

class StubChannelReportService implements ChannelReportServiceInterface
{
    public function getReportData(ChannelReportQueryData $query): TableReportData
    {
        return $this->flatReport();
    }

    public function flatReport(): TableReportData
    {
        $report = new TableReportData();
        $group = new TableReportGroupData();

        $group->rows = new Collection([
            new Collection(array_merge(
                $this->createClientData('SEO', 'ООО "ТД ПЗЭМ"', 'pzem.ru', 2706, 'active'),
                $this->createTeamData('Евгений Э.', 1, 'Александр С.', 2),
                $this->createFinancialData('Трафик', 5130, 88000, 4000, 91000),
                $this->createSpendingsData(
                    [
                        'hours' => 0,
                        'sum' => 1217
                    ],
                    [
                        'hours' => 1,
                        'sum' => 180
                    ],
                    786,
                    [
                        'position_1' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_2' => [
                            'hours' => 19.25,
                            'sum' => 41101
                        ],
                        'position_3' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_4' => [
                            'hours' => 0,
                            'sum' => 0
                        ]
                    ]
                )
            )),
            new Collection(array_merge(
                $this->createClientData('SEO', 'ООО “ПРАЙМ-1С-ЕКАТЕРИНБУРГ”', '1c-prime.ru', 2710, 'active'),
                $this->createTeamData('Евгений Э.', 1, 'Александр С.', 2),
                $this->createFinancialData('Трафик', 2207, 55000, 10000, 55000),
                $this->createSpendingsData(
                    [
                        'hours' => 1,
                        'sum' => 1800
                    ],
                    [
                        'hours' => 0,
                        'sum' => 0
                    ],
                    482,
                    [
                        'position_1' => [
                            'hours' => 4.5,
                            'sum' => 7470
                        ],
                        'position_2' => [
                            'hours' => 14.92,
                            'sum' => 31849
                        ],
                        'position_3' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_4' => [
                            'hours' => 0,
                            'sum' => 0
                        ]
                    ]
                )
            )),
            new Collection(array_merge(
                $this->createClientData('SEO', 'ИП Пахомчик В.Н.', 'chestnuyput.ru', 2712, 'active'),
                $this->createTeamData('Наталия. Б', 1, 'Мария. Б', 2),
                $this->createFinancialData('Трафик', 107, 40000, 20000, 40000),
                $this->createSpendingsData(
                    [
                        'hours' => 0,
                        'sum' => 0
                    ],
                    [
                        'hours' => 0,
                        'sum' => 0
                    ],
                    0,
                    [
                        'position_1' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_2' => [
                            'hours' => 18.41,
                            'sum' => 39322
                        ],
                        'position_3' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_4' => [
                            'hours' => 1.33,
                            'sum' => 3144
                        ]
                    ]
                )
            )),
            new Collection(array_merge(
                $this->createClientData('SEO', 'ООО “ММК-МЕТИЗ”', 'mmk-metiz.ru', 2713, 'inactive'),
                $this->createTeamData('Екатерина. М', 1, 'Мария. Б', 2),
                $this->createFinancialData('Позиции', '50%', 27500, null, 38698),
                $this->createSpendingsData(
                    [
                        'hours' => 0,
                        'sum' => 0
                    ],
                    [
                        'hours' => 0,
                        'sum' => 0
                    ],
                    1134,
                    [
                        'position_1' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_2' => [
                            'hours' => 13.34,
                            'sum' => 28467
                        ],
                        'position_3' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_4' => [
                            'hours' => 0.25,
                            'sum' => 590
                        ]
                    ]
                )
            )),
            new Collection(array_merge(
                $this->createClientData('Контекст', 'ИП Нетесов', 'example.com', 2714, 'active'),
                $this->createTeamData('Екатерина. М', 1, 'Марина. Х', 2),
                $this->createFinancialData('Показы', '100000', 30000, 0, null),
                $this->createSpendingsData(
                    [
                        'hours' => 0,
                        'sum' => 0
                    ],
                    [
                        'hours' => 0,
                        'sum' => 0
                    ],
                    0,
                    [
                        'position_1' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_2' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_3' => [
                            'hours' => 0,
                            'sum' => 0
                        ],
                        'position_4' => [
                            'hours' => 0,
                            'sum' => 0
                        ]
                    ]
                )
            ))
        ]);

        $report->groups = new Collection([$group]);

        $report->summary = new Collection([
            'client' => [
                'count' => 8
            ],
            'client_project' => [
                'count' => 9
            ],
            'status' => [
                'active' => 7,
                'inactive' => 1
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

    public function createFinancialData(string $kpi, int|string $plan, int $clientReceipt, ?int $maxBonuses, ?int $acts): array
    {
        return [
            'kpi' => $kpi,
            'plan' => $plan,
            'client_receipt' => $clientReceipt,
            'max_bonuses' => $maxBonuses,
            'acts' => $acts
        ];
    }

    public function createSpendingsData(array $programming, array $copyrighting, int $seoLinksSum, array $positions): array
    {
        $spendings = [
            'programming' => $programming,
            'copyrighting' => $copyrighting,
            'seo_links' => ['sum' => $seoLinksSum]
        ];

        foreach ($positions as $key => $position) {
            $spendings[$key] = $position;
        }

        $totalSum = $programming['sum'] + $copyrighting['sum'] + $seoLinksSum;
        foreach ($positions as $position) {
            $totalSum += $position['sum'];
        }

        $spendings['summary_spendings'] = ['sum' => $totalSum];

        return $spendings;
    }
}
