<?php

namespace Src\Infrastructure\Reports;

use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use Illuminate\Support\Facades\Storage;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportDataBuilder;
use Src\Application\Reports\Generate\ReportDataProviderInterface;
use Src\Domain\Agencies\AgencyRepositoryInterface;
use Src\Domain\Clients\ClientRepositoryInterface;
use Src\Domain\CompletedWorks\CompletedWorkRepositoryInterface;
use Src\Domain\Leads\CallibriLead;
use Src\Domain\Leads\CallibriLeadRepositoryInterface;
use Src\Domain\Projects\ProjectPlanValueRepositoryInterface;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Serp\SerpPositionRepositoryInterface;
use Src\Domain\Users\UserRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaGoalConversion;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectSummary;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats;
use Src\Domain\YandexMetrika\YandexMetrikaVisitsGeo;
use Src\Domain\YandexMetrika\YandexMetrikaVisitsSearchQueries;

class ReportDataProvider implements ReportDataProviderInterface
{
    private readonly ProjectRepositoryInterface $projectRepository;

    private readonly ClientRepositoryInterface $clientRepository;

    private readonly UserRepositoryInterface $userRepository;

    private readonly CallibriLeadRepositoryInterface $callibriLeadRepository;

    private readonly SerpPositionRepositoryInterface $serpPositionRepository;

    private readonly YandexDirectRepositoryInterface $yandexDirectRepository;

    private readonly YandexMetrikaRepositoryInterface $yandexMetrikaRepository;

    private readonly ProjectPlanValueRepositoryInterface $projectPlanValueRepository;

    private readonly CompletedWorkRepositoryInterface $completedWorkRepository;

    private readonly AgencyRepositoryInterface $agencyRepository;

    private readonly IntegrationProjectCredentials $integrationProjectCredentials;

    public function __construct(
        ProjectRepositoryInterface $projectRepository,
        ClientRepositoryInterface $clientRepository,
        UserRepositoryInterface $userRepository,
        CallibriLeadRepositoryInterface $callibriLeadRepository,
        SerpPositionRepositoryInterface $serpPositionRepository,
        YandexDirectRepositoryInterface $yandexDirectRepository,
        YandexMetrikaRepositoryInterface $yandexMetrikaRepository,
        ProjectPlanValueRepositoryInterface $projectPlanValueRepository,
        CompletedWorkRepositoryInterface $completedWorkRepository,
        AgencyRepositoryInterface $agencyRepository,
        IntegrationProjectCredentials $integrationProjectCredentials,
    ) {
        $this->projectRepository = $projectRepository;
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
        $this->callibriLeadRepository = $callibriLeadRepository;
        $this->serpPositionRepository = $serpPositionRepository;
        $this->yandexDirectRepository = $yandexDirectRepository;
        $this->yandexMetrikaRepository = $yandexMetrikaRepository;
        $this->projectPlanValueRepository = $projectPlanValueRepository;
        $this->completedWorkRepository = $completedWorkRepository;
        $this->agencyRepository = $agencyRepository;
        $this->integrationProjectCredentials = $integrationProjectCredentials;
    }

    public function getData(int $projectId, DateTimeRange $period): ReportData
    {
        $builder = ReportData::builder()
            ->value('current_year', date('Y'))
            ->value('period_from', $period->start->format('d.m.Y'))
            ->value('period_to', $period->end->format('d.m.Y'))
            ->value('period_from_text', $period->startDateLabel())
            ->value('period_to_text', $period->endDateLabel())
            ->value('period_month', $period->monthYearLabel());

        $project = $this->projectRepository->findById($projectId);
        $builder->value('project_domain', $project->getDomain())
            ->value('project_name', $project->getName())
            ->value('manager_last_name', '')
            ->value('manager_first_name', '')
            ->value('manager_phone', '')
            ->value('manager_email', '')
            ->image('manager_photo', '')
            ->value('agency_address', '')
            ->value('agency_domain', '')
            ->image('agency_image', '')
            ->value('agency_email', '')
            ->value('agency_phone', '');

        $callibriLeads = [];
        $yandexDirectStats = [];
        $searchEnginesStats = [];
        $goalsUtmStats = [];

        $this->safely($builder, 'Данные менеджера не загружены', function () use ($builder, $project): void {
            $client = $this->clientRepository->findById($project->getClientId());
            $manager = $this->userRepository->findById($client->getManagerId());
            $builder->value('manager_last_name', $manager->getLastName())
                ->value('manager_first_name', $manager->getFirstName())
                ->value('manager_phone', $manager->getPhone())
                ->value('manager_email', $manager->getEmail())
                ->image('manager_photo', ! empty($manager->getImagePath()) ? Storage::disk('public')->path($manager->getImagePath()) : '');

            $agencyId = empty($manager->getAgencysIds()) ? null : $manager->getAgencysIds()[0];
            if ($agencyId === null) {
                $builder->value('agency_address', '')
                    ->value('agency_domain', '')
                    ->image('agency_image', '')
                    ->value('agency_email', '')
                    ->value('agency_phone', '');

                return;
            }

            $agency = $this->agencyRepository->findById($agencyId);
            $builder->value('agency_address', $agency->getAddress())
                ->value('agency_domain', $agency->getDomain())
                ->image('agency_image', ! empty($agency->getLogoPath()) ? Storage::disk('public')->path($agency->getLogoPath()) : '')
                ->value('agency_email', $agency->getEmail())
                ->value('agency_phone', $agency->getPhone());
        });

        $this->safely($builder, 'Данные Callibri не загружены', function () use ($builder, $projectId, $period, &$callibriLeads): void {
            $callibriLeads = $this->callibriLeadRepository->findByProjectId($projectId, $period);
            $callibriTableRows = array_map(fn ($lead) => [
                $lead->getDate()->format('d.m.Y'),
                '',
                '',
                $lead->getUtmSource(),
                $lead->getUtmMedium(),
                $lead->getUtmCampaign(),
                $lead->getUtmContent(),
                $lead->getUtmTerm(),
            ], $callibriLeads);
            $builder->table('callibri.table', [
                'Дата',
                'Класс',
                'Тип обращения',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_term',
            ], $callibriTableRows);
        });

        $this->safely($builder, 'Данные позиций Яндекс не загружены', function () use ($builder, $projectId, $period): void {
            $settings = $this->integrationProjectCredentials->settingsFor($projectId, 'yandex_search_api') ?? [];
            $regions = is_array($settings['regions'] ?? null) ? $settings['regions'] : [];

            foreach (YandexSearchIndexedTags::numberedRegions($regions) as $region) {
                $index = $region['index'];
                $builder->value(YandexSearchIndexedTags::key('region', $index), $region['label']);

                $keywordPositions = $this->serpPositionRepository->getKeywordPositions(
                    $projectId,
                    $period,
                    'yandex',
                    $region['code'],
                );
                $shares = YandexSearchIndexedTags::topShares(array_column($keywordPositions, 'position'));
                $builder->value(YandexSearchIndexedTags::key('top_3', $index), YandexSearchIndexedTags::formatShare($shares['top_3']))
                    ->value(YandexSearchIndexedTags::key('top_5', $index), YandexSearchIndexedTags::formatShare($shares['top_5']))
                    ->value(YandexSearchIndexedTags::key('top_10', $index), YandexSearchIndexedTags::formatShare($shares['top_10']));

                $pairRows = array_map(
                    fn ($item) => [
                        'phrase' => $item['phrase'],
                        'position' => (string) ($item['position'] ?? '-'),
                    ],
                    $keywordPositions
                );
                $builder->keywordPositionPair(
                    YandexSearchIndexedTags::key('phrase', $index),
                    YandexSearchIndexedTags::key('position', $index),
                    $pairRows,
                );
            }
        });

        $this->safely($builder, 'Данные Яндекс.Директ не загружены', function () use ($builder, $projectId, $period, &$yandexDirectStats): void {
            $yandexDirectStats = $this->yandexDirectRepository->findByProjectId($projectId, $period);
            $yandexDirectTableRows = array_map(
                fn ($stats) => [
                    $stats->getCampaignName(),
                    $stats->getImpressions(),
                    $stats->getClicks(),
                    $stats->getCostWithVat(),
                    $stats->getCostWithoutVat(),
                    $stats->getCtr().'%',
                    $stats->getCpc(),
                    $stats->getConversions(),
                    $stats->getCpl(),
                    $stats->getGoalName() ?? '—',
                ],
                $yandexDirectStats
            );
            $builder->table('yd.table', [
                'Кампания',
                'Показы',
                'Клики',
                'Расход с НДС',
                'Расход без НДС',
                'CTR',
                'CPC',
                'Конверсии',
                'CPL',
                'Название цели',
            ], $yandexDirectTableRows);
        });

        $this->safely($builder, 'Данные целей Яндекс.Метрики не загружены', function () use ($builder, $projectId, $period, &$searchEnginesStats): void {
            $searchEnginesStats = $this->yandexMetrikaRepository->getSearchEnginesStats($projectId, $period);
            $searchEnginesTable = $this->buildSearchEnginesTable($searchEnginesStats, $period);
            $builder->table('ym.table.goals_search_engines', $searchEnginesTable['headers'], $searchEnginesTable['rows']);

            $yandexTable = $this->buildSingleSearchEngineTable($searchEnginesStats, $period, 'yandex');
            $builder->table('ym.table.goals_search_engines.yandex', $yandexTable['headers'], $yandexTable['rows']);

            $googleTable = $this->buildSingleSearchEngineTable($searchEnginesStats, $period, 'google');
            $builder->table('ym.table.goals_search_engines.google', $googleTable['headers'], $googleTable['rows']);
        });

        $this->safely($builder, 'Данные UTM-целей Яндекс.Метрики не загружены', function () use ($builder, $projectId, $period, &$goalsUtmStats): void {
            $goalsUtmStats = $this->yandexMetrikaRepository->getGoalUtmStats($projectId, $period);
            $goalsUtmTable = $this->buildGoalsUtmTable($goalsUtmStats);
            $builder->table('ym.table.goals_utm', $goalsUtmTable['headers'], $goalsUtmTable['rows']);
        });

        $this->safely($builder, 'Данные конверсий Яндекс.Метрики не загружены', function () use ($builder, $projectId, $period): void {
            $goalConversionsStats = $this->yandexMetrikaRepository->getGoalConversionsStats($projectId, $period);
            $goalsConversionsTable = $this->buildGoalsConversionsTable($goalConversionsStats, $period);
            $builder->table('ym.table.goals_conversions', $goalsConversionsTable['headers'], $goalsConversionsTable['rows']);
        });

        $this->safely($builder, 'Сводка целей Директа не загружена', function () use ($builder, $projectId, $period): void {
            $goalDirectSummaryStats = $this->yandexMetrikaRepository->getGoalDirectSummaryStats($projectId, $period);
            $goalsDirectSummaryTable = $this->buildGoalsDirectSummaryTable($goalDirectSummaryStats, $period);
            $builder->table('ym.table.goals_direct_summary', $goalsDirectSummaryTable['headers'], $goalsDirectSummaryTable['rows']);
        });

        $this->safely($builder, 'Таблица визитов из поиска не загружена', function () use ($builder, $projectId, $period, $searchEnginesStats): void {
            $visitsSearchSystemsTable = $this->buildVisitsSearchSystemsTable($projectId, $searchEnginesStats, $period);
            $builder->table('ym.table.visits_search_systems', $visitsSearchSystemsTable['headers'], $visitsSearchSystemsTable['rows']);
        });
        $planVisits = 0;
        $this->safely($builder, 'План посещаемости не загружен', function () use ($projectId, $period, &$planVisits): void {
            $planVisits = $this->projectPlanValueRepository->sumByCode($projectId, 'visits', $period);
        });
        $builder->values(YandexMetrikaSearchVisits::tagValues($searchEnginesStats, $planVisits));

        $this->safely($builder, 'Таблица визитов по географии не загружена', function () use ($builder, $projectId, $period): void {
            $visitsGeoStats = $this->yandexMetrikaRepository->getVisitsGeoStats($projectId, $period);
            $visitsGeoTable = $this->buildVisitsGeoTable($visitsGeoStats);
            $builder->table('ym.table.visits_geo', $visitsGeoTable['headers'], $visitsGeoTable['rows']);
        });

        $this->safely($builder, 'Таблица визитов по запросам не загружена', function () use ($builder, $projectId, $period): void {
            $visitsSearchQueriesStats = $this->yandexMetrikaRepository->getVisitsSearchQueriesStats($projectId, $period);
            $visitsSearchQueriesTable = $this->buildVisitsSearchQueriesTable($visitsSearchQueriesStats);
            $builder->table('ym.table.visits_search_queries', $visitsSearchQueriesTable['headers'], $visitsSearchQueriesTable['rows']);

            $settings = $this->integrationProjectCredentials->settingsFor($projectId, 'yandex_metrika') ?? [];
            $useVisitors = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($settings['visits_metric'] ?? null)
                === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS;
            $builder->keywordPositionPair(
                YandexMetrikaSearchQueries::PHRASE_KEY,
                YandexMetrikaSearchQueries::VISITS_KEY,
                YandexMetrikaSearchQueries::pairRows($visitsSearchQueriesStats, $useVisitors),
            );
        });

        $this->safely($builder, 'Список выполненных работ не загружен', function () use ($builder, $projectId, $period): void {
            $completedWorks = $this->completedWorkRepository->findByProjectId($projectId, $period);
            $completedWorksList = array_map(
                fn ($work) => $work->getTitle(),
                $completedWorks
            );
            $builder->list('bitrix24.worklist', $completedWorksList);
        });

        $this->safely($builder, 'Таблица сопоставления кампаний не загружена', function () use ($builder, $yandexDirectStats, $goalsUtmStats, $callibriLeads): void {
            $utmCampaignMatchTable = $this->buildUtmCampaignMatchTable(
                $yandexDirectStats,
                $goalsUtmStats,
                $callibriLeads
            );
            $builder->table('yd.table.utm_campaign_match', $utmCampaignMatchTable['headers'], $utmCampaignMatchTable['rows']);
        });

        return $builder->build();
    }

    /**
     * @param  callable(ReportDataBuilder): void  $callback
     */
    private function safely(ReportDataBuilder $builder, string $warning, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            report($e);
            $builder->warning($warning);
        }
    }

    /**
     * Строит таблицу статистики по поисковым системам.
     *
     * @param  array<YandexMetrikaSearchEnginesStats>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildSearchEnginesTable(array $stats, DateTimeRange $period): array
    {
        // Формируем список месяцев в периоде
        $months = [];
        $current = $period->start->modify('first day of this month');
        $endMonth = $period->end->modify('first day of this month');

        while ($current <= $endMonth) {
            $months[] = clone $current;
            $current = $current->modify('+1 month');
        }

        // Заголовки: Поисковая система | Месяц1 | Месяц2 | ... | Итого
        $headers = ['Поисковая система'];
        foreach ($months as $month) {
            $headers[] = $this->formatMonthHeader($month);
        }
        $headers[] = 'Итого';

        // Группируем данные по поисковым системам и месяцам
        $data = [];
        foreach ($stats as $item) {
            $engine = $item->getSearchEngine();
            $monthKey = $item->getMonth()->format('Y-m-01');

            if (! isset($data[$engine])) {
                $data[$engine] = [];
            }

            $data[$engine][$monthKey] = $item->getConversions();
        }

        // Формируем строки таблицы
        $rows = [];
        $engineLabels = [
            'yandex' => 'Яндекс',
            'google' => 'Google',
            'other' => 'другие',
        ];

        $columnTotals = array_fill(0, count($months), 0);
        $grandTotal = 0;

        foreach (['yandex', 'google', 'other'] as $engine) {
            $row = [$engineLabels[$engine]];
            $rowTotal = 0;

            foreach ($months as $month) {
                $monthKey = $month->format('Y-m-01');
                $value = $data[$engine][$monthKey] ?? 0;
                $row[] = $value;
                $rowTotal += $value;
                $columnTotals[array_search($month, $months)] += $value;
            }

            $row[] = $rowTotal;
            $rows[] = $row;
            $grandTotal += $rowTotal;
        }

        // Строка "Итого"
        $totalRow = ['Итого'];
        foreach ($columnTotals as $total) {
            $totalRow[] = $total;
        }
        $totalRow[] = $grandTotal;
        $rows[] = $totalRow;

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Строит таблицу статистики для конкретной поисковой системы.
     *
     * @param  array<YandexMetrikaSearchEnginesStats>  $stats
     * @param  string  $engineName  Имя поисковой системы (yandex, google)
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildSingleSearchEngineTable(array $stats, DateTimeRange $period, string $engineName): array
    {
        // Формируем список месяцев в периоде
        $months = [];
        $current = $period->start->modify('first day of this month');
        $endMonth = $period->end->modify('first day of this month');

        while ($current <= $endMonth) {
            $months[] = clone $current;
            $current = $current->modify('+1 month');
        }

        // Заголовки: Показатель | Месяц1 | Месяц2 | ... | Итого
        $headers = ['Показатель'];
        foreach ($months as $month) {
            $headers[] = $this->formatMonthHeader($month);
        }
        $headers[] = 'Итого';

        // Фильтруем данные по конкретной поисковой системе
        $filteredStats = array_filter($stats, fn ($item) => $item->getSearchEngine() === $engineName);

        // Группируем данные по месяцам
        $conversionsByMonth = [];
        $visitsByMonth = [];
        foreach ($filteredStats as $item) {
            $monthKey = $item->getMonth()->format('Y-m-01');
            $conversionsByMonth[$monthKey] = $item->getConversions();
            $visitsByMonth[$monthKey] = $item->getVisits();
        }

        // Формируем строки таблицы
        $rows = [];

        // Строка "Конверсии"
        $conversionsRow = ['Конверсии'];
        $conversionsTotal = 0;
        foreach ($months as $month) {
            $monthKey = $month->format('Y-m-01');
            $value = $conversionsByMonth[$monthKey] ?? 0;
            $conversionsRow[] = $value;
            $conversionsTotal += $value;
        }
        $conversionsRow[] = $conversionsTotal;
        $rows[] = $conversionsRow;

        // Строка "Визиты"
        $visitsRow = ['Визиты'];
        $visitsTotal = 0;
        foreach ($months as $month) {
            $monthKey = $month->format('Y-m-01');
            $value = $visitsByMonth[$monthKey] ?? 0;
            $visitsRow[] = $value;
            $visitsTotal += $value;
        }
        $visitsRow[] = $visitsTotal;
        $rows[] = $visitsRow;

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Форматирует заголовок месяца.
     */
    private function formatMonthHeader(\DateTimeImmutable $month): string
    {
        $months = [
            1 => 'Янв',
            2 => 'Фев',
            3 => 'Мар',
            4 => 'Апр',
            5 => 'Май',
            6 => 'Июн',
            7 => 'Июл',
            8 => 'Авг',
            9 => 'Сен',
            10 => 'Окт',
            11 => 'Ноя',
            12 => 'Дек',
        ];

        return $months[(int) $month->format('n')].' '.$month->format('Y');
    }

    /**
     * Строит таблицу достижений целей с UTM-метками.
     *
     * @param  array<YandexMetrikaGoalUtm>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildGoalsUtmTable(array $stats): array
    {
        $headers = [
            'Название цели',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
            'Итого',
        ];

        // Группируем данные по целям и считаем UTM
        $aggregated = [];
        foreach ($stats as $item) {
            $goalName = $item->getGoalName();

            if (! isset($aggregated[$goalName])) {
                $aggregated[$goalName] = [
                    'utm_source' => 0,
                    'utm_medium' => 0,
                    'utm_campaign' => 0,
                    'utm_content' => 0,
                    'utm_term' => 0,
                    'total' => 0,
                ];
            }

            $aggregated[$goalName]['total']++;

            if ($item->getUtmSource() !== null) {
                $aggregated[$goalName]['utm_source']++;
            }
            if ($item->getUtmMedium() !== null) {
                $aggregated[$goalName]['utm_medium']++;
            }
            if ($item->getUtmCampaign() !== null) {
                $aggregated[$goalName]['utm_campaign']++;
            }
            if ($item->getUtmContent() !== null) {
                $aggregated[$goalName]['utm_content']++;
            }
            if ($item->getUtmTerm() !== null) {
                $aggregated[$goalName]['utm_term']++;
            }
        }

        // Формируем строки таблицы
        $rows = [];
        $columnTotals = [
            'utm_source' => 0,
            'utm_medium' => 0,
            'utm_campaign' => 0,
            'utm_content' => 0,
            'utm_term' => 0,
            'total' => 0,
        ];

        foreach ($aggregated as $goalName => $counts) {
            $rows[] = [
                $goalName,
                $counts['utm_source'],
                $counts['utm_medium'],
                $counts['utm_campaign'],
                $counts['utm_content'],
                $counts['utm_term'],
                $counts['total'],
            ];

            foreach ($counts as $key => $value) {
                $columnTotals[$key] += $value;
            }
        }

        // Строка "Итого"
        $rows[] = [
            'Итого',
            $columnTotals['utm_source'],
            $columnTotals['utm_medium'],
            $columnTotals['utm_campaign'],
            $columnTotals['utm_content'],
            $columnTotals['utm_term'],
            $columnTotals['total'],
        ];

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Строит таблицу достижений целей из отчёта "Конверсии".
     *
     * @param  array<YandexMetrikaGoalConversion>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildGoalsConversionsTable(array $stats, DateTimeRange $period): array
    {
        // Формируем список месяцев в периоде
        $months = [];
        $current = $period->start->modify('first day of this month');
        $endMonth = $period->end->modify('first day of this month');

        while ($current <= $endMonth) {
            $months[] = clone $current;
            $current = $current->modify('+1 month');
        }

        // Заголовки: Название цели | Месяц1 | Месяц2 | ... | Итого
        $headers = ['Название цели'];
        foreach ($months as $month) {
            $headers[] = $this->formatMonthHeader($month);
        }
        $headers[] = 'Итого';

        // Группируем данные по целям и месяцам
        $data = [];
        foreach ($stats as $item) {
            $goalName = $item->getGoalName();
            $monthKey = $item->getMonth()->format('Y-m-01');

            if (! isset($data[$goalName])) {
                $data[$goalName] = [];
            }

            $data[$goalName][$monthKey] = $item->getConversions();
        }

        // Формируем строки таблицы
        $rows = [];
        $columnTotals = array_fill(0, count($months), 0);
        $grandTotal = 0;

        foreach ($data as $goalName => $monthlyData) {
            $row = [$goalName];
            $rowTotal = 0;

            foreach ($months as $month) {
                $monthKey = $month->format('Y-m-01');
                $value = $monthlyData[$monthKey] ?? 0;
                $row[] = $value;
                $rowTotal += $value;
                $columnTotals[array_search($month, $months)] += $value;
            }

            $row[] = $rowTotal;
            $rows[] = $row;
            $grandTotal += $rowTotal;
        }

        // Строка "Итого"
        $totalRow = ['Итого'];
        foreach ($columnTotals as $total) {
            $totalRow[] = $total;
        }
        $totalRow[] = $grandTotal;
        $rows[] = $totalRow;

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Строит таблицу достижений целей из отчёта "Директ, сводка".
     *
     * @param  array<YandexMetrikaGoalDirectSummary>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildGoalsDirectSummaryTable(array $stats, DateTimeRange $period): array
    {
        $months = [];
        $current = $period->start->modify('first day of this month');
        $endMonth = $period->end->modify('first day of this month');

        while ($current <= $endMonth) {
            $months[] = clone $current;
            $current = $current->modify('+1 month');
        }

        $headers = ['Название цели'];
        foreach ($months as $month) {
            $headers[] = $this->formatMonthHeader($month);
        }
        $headers[] = 'Итого';

        $data = [];
        foreach ($stats as $item) {
            $goalName = $item->getGoalName();
            $monthKey = $item->getMonth()->format('Y-m-01');

            if (! isset($data[$goalName])) {
                $data[$goalName] = [];
            }

            $data[$goalName][$monthKey] = $item->getConversions();
        }

        $rows = [];
        $columnTotals = array_fill(0, count($months), 0);
        $grandTotal = 0;

        foreach ($data as $goalName => $monthlyData) {
            $row = [$goalName];
            $rowTotal = 0;

            foreach ($months as $month) {
                $monthKey = $month->format('Y-m-01');
                $value = $monthlyData[$monthKey] ?? 0;
                $row[] = $value;
                $rowTotal += $value;
                $columnTotals[array_search($month, $months)] += $value;
            }

            $row[] = $rowTotal;
            $rows[] = $row;
            $grandTotal += $rowTotal;
        }

        $totalRow = ['Итого'];
        foreach ($columnTotals as $total) {
            $totalRow[] = $total;
        }
        $totalRow[] = $grandTotal;
        $rows[] = $totalRow;

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Строит таблицу "Переходы из поисковых систем" с планом и фактом.
     *
     * @param  array<YandexMetrikaSearchEnginesStats>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int|float|null>>}
     */
    private function buildVisitsSearchSystemsTable(int $projectId, array $stats, DateTimeRange $period): array
    {
        $headers = ['Название', 'План', 'Факт'];

        $planValues = $this->projectPlanValueRepository->findByCodes(
            $projectId,
            ['visits'],
            $period
        );

        $totalPlan = isset($planValues['visits']) && $planValues['visits'] !== null
            ? (int) $planValues['visits']->getValue()
            : null;

        $legacyLabels = [
            'yandex' => 'Яндекс',
            'google' => 'Google',
            'bing' => 'Bing',
            'mail' => 'Mail.ru',
            'yahoo' => 'Yahoo',
            'rambler' => 'Rambler',
            'duckduckgo' => 'DuckDuckGo',
            'other' => 'другие',
        ];

        $factData = [];
        foreach ($stats as $item) {
            $visits = $item->getVisits();
            if ($visits <= 0) {
                continue;
            }

            $engine = $item->getSearchEngine();
            $label = $legacyLabels[$engine] ?? $engine;

            if (! isset($factData[$label])) {
                $factData[$label] = 0;
            }
            $factData[$label] += $visits;
        }

        $rows = [];
        $totalFact = 0;

        foreach ($factData as $label => $factValue) {
            $totalFact += $factValue;

            $rows[] = [
                $label,
                '—',
                $factValue,
            ];
        }

        $rows[] = [
            'Итого',
            $totalPlan ?? '—',
            $totalFact,
        ];

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Строит таблицу "Переходы из отчёта География Яндекс Метрики".
     *
     * @param  array<YandexMetrikaVisitsGeo>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildVisitsGeoTable(array $stats): array
    {
        $headers = [
            'Город',
            'Визиты',
            'Посетители',
            'Достижения целей',
        ];

        // Группируем данные по городам и суммируем показатели
        $aggregated = [];
        foreach ($stats as $item) {
            $city = $item->getCity();

            if (! isset($aggregated[$city])) {
                $aggregated[$city] = [
                    'visits' => 0,
                    'visitors' => 0,
                    'goal_reaches' => 0,
                ];
            }

            $aggregated[$city]['visits'] += $item->getVisits();
            $aggregated[$city]['visitors'] += $item->getVisitors();
            $aggregated[$city]['goal_reaches'] += $item->getGoalReaches();
        }

        // Сортируем по количеству визитов по убыванию
        uasort($aggregated, fn ($a, $b) => $b['visits'] <=> $a['visits']);

        // Формируем строки таблицы
        $rows = [];
        $totalVisits = 0;
        $totalVisitors = 0;
        $totalGoalReaches = 0;

        foreach ($aggregated as $city => $data) {
            $rows[] = [
                $city,
                $data['visits'],
                $data['visitors'],
                $data['goal_reaches'],
            ];

            $totalVisits += $data['visits'];
            $totalVisitors += $data['visitors'];
            $totalGoalReaches += $data['goal_reaches'];
        }

        // Строка "Итого"
        $rows[] = [
            'Итого',
            $totalVisits,
            $totalVisitors,
            $totalGoalReaches,
        ];

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Строит таблицу "Переходы из отчёта поисковые запросы Яндекс Метрики".
     *
     * @param  array<YandexMetrikaVisitsSearchQueries>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildVisitsSearchQueriesTable(array $stats): array
    {
        $headers = [
            'Фраза',
            'Визиты',
            'Посетители',
            'Достижения целей',
        ];

        // Группируем данные по фразам и суммируем показатели
        $aggregated = [];
        foreach ($stats as $item) {
            $phrase = $item->getPhrase();

            if (! isset($aggregated[$phrase])) {
                $aggregated[$phrase] = [
                    'visits' => 0,
                    'visitors' => 0,
                    'goal_reaches' => 0,
                ];
            }

            $aggregated[$phrase]['visits'] += $item->getVisits();
            $aggregated[$phrase]['visitors'] += $item->getVisitors();
            $aggregated[$phrase]['goal_reaches'] += $item->getGoalReaches();
        }

        // Сортируем по количеству визитов по убыванию
        uasort($aggregated, fn ($a, $b) => $b['visits'] <=> $a['visits']);

        // Формируем строки таблицы
        $rows = [];
        $totalVisits = 0;
        $totalVisitors = 0;
        $totalGoalReaches = 0;

        foreach ($aggregated as $phrase => $data) {
            $rows[] = [
                $phrase,
                $data['visits'],
                $data['visitors'],
                $data['goal_reaches'],
            ];

            $totalVisits += $data['visits'];
            $totalVisitors += $data['visitors'];
            $totalGoalReaches += $data['goal_reaches'];
        }

        // Строка "Итого"
        $rows[] = [
            'Итого',
            $totalVisits,
            $totalVisitors,
            $totalGoalReaches,
        ];

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Строит таблицу "Сопоставление по utm_campaign".
     *
     * @param  array<YandexDirectCampaignStats>  $yandexDirectStats
     * @param  array<YandexMetrikaGoalUtm>  $goalsUtmStats
     * @param  array<CallibriLead>  $callibriLeads
     * @return array{headers: array<string>, rows: array<array<string|int|float>>}
     */
    private function buildUtmCampaignMatchTable(
        array $yandexDirectStats,
        array $goalsUtmStats,
        array $callibriLeads
    ): array {
        $headers = [
            'Кампания',
            'Показы',
            'Клики',
            'Расход с НДС',
            'Расход без НДС',
            'CTR',
            'CPC',
            'Конверсии',
            'CPL',
            'Название цели',
        ];

        // Группируем конверсии из Яндекс.Метрики по utm_campaign
        $metrikaConversionsByCampaign = [];
        foreach ($goalsUtmStats as $goal) {
            $utmCampaign = $goal->getUtmCampaign();
            if ($utmCampaign !== null) {
                if (! isset($metrikaConversionsByCampaign[$utmCampaign])) {
                    $metrikaConversionsByCampaign[$utmCampaign] = 0;
                }
                $metrikaConversionsByCampaign[$utmCampaign]++;
            }
        }

        // Группируем лиды из Callibri по utm_campaign
        $callibriLeadsByCampaign = [];
        foreach ($callibriLeads as $lead) {
            $utmCampaign = $lead->getUtmCampaign();
            if ($utmCampaign !== null) {
                if (! isset($callibriLeadsByCampaign[$utmCampaign])) {
                    $callibriLeadsByCampaign[$utmCampaign] = 0;
                }
                $callibriLeadsByCampaign[$utmCampaign]++;
            }
        }

        // Создаём маппинг campaign_id => campaign_name для сопоставления
        $campaignIdToName = [];
        foreach ($yandexDirectStats as $stats) {
            $campaignId = $stats->getCampaignId();
            if ($campaignId !== null) {
                $campaignIdToName[(string) $campaignId] = $stats->getCampaignName();
            }
        }

        // Отслеживаем использованные utm_campaign
        $usedMetrikaCampaigns = [];
        $usedCallibriCampaigns = [];

        // Формируем строки для кампаний из Яндекс.Директ
        $rows = [];
        foreach ($yandexDirectStats as $stats) {
            $campaignId = $stats->getCampaignId();
            $campaignIdStr = $campaignId !== null ? (string) $campaignId : null;

            // Базовые конверсии из Директа
            $conversions = $stats->getConversions();

            // Добавляем конверсии из Метрики при совпадении campaign_id с utm_campaign
            if ($campaignIdStr !== null && isset($metrikaConversionsByCampaign[$campaignIdStr])) {
                $conversions += $metrikaConversionsByCampaign[$campaignIdStr];
                $usedMetrikaCampaigns[$campaignIdStr] = true;
            }

            // Добавляем лиды из Callibri при совпадении campaign_id с utm_campaign
            if ($campaignIdStr !== null && isset($callibriLeadsByCampaign[$campaignIdStr])) {
                $conversions += $callibriLeadsByCampaign[$campaignIdStr];
                $usedCallibriCampaigns[$campaignIdStr] = true;
            }

            // Пересчитываем CPL с учётом новых конверсий
            $cpl = $conversions > 0
                ? round($stats->getCostWithVat() / $conversions, 2)
                : 0.0;

            $rows[] = [
                $stats->getCampaignName(),
                $stats->getImpressions(),
                $stats->getClicks(),
                $stats->getCostWithVat(),
                $stats->getCostWithoutVat(),
                $stats->getCtr().'%',
                $stats->getCpc(),
                $conversions,
                $cpl,
                $stats->getGoalName() ?? '—',
            ];
        }

        // Добавляем строки для нераспределённых конверсий из Метрики
        foreach ($metrikaConversionsByCampaign as $utmCampaign => $count) {
            if (! isset($usedMetrikaCampaigns[$utmCampaign])) {
                $rows[] = [
                    "Метрика (utm_campaign: {$utmCampaign})",
                    '—',
                    '—',
                    '—',
                    '—',
                    '—',
                    '—',
                    $count,
                    '—',
                    '—',
                ];
            }
        }

        // Добавляем строки для нераспределённых лидов из Callibri
        foreach ($callibriLeadsByCampaign as $utmCampaign => $count) {
            if (! isset($usedCallibriCampaigns[$utmCampaign])) {
                $rows[] = [
                    "Callibri (utm_campaign: {$utmCampaign})",
                    '—',
                    '—',
                    '—',
                    '—',
                    '—',
                    '—',
                    $count,
                    '—',
                    '—',
                ];
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }
}
