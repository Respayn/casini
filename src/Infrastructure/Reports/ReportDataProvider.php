<?php

namespace Src\Infrastructure\Reports;

use Illuminate\Support\Facades\Storage;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportDataProviderInterface;
use Src\Domain\Agencies\AgencyRepositoryInterface;
use Src\Domain\Clients\ClientRepositoryInterface;
use Src\Domain\CompletedWorks\CompletedWorkRepositoryInterface;
use Src\Domain\Leads\CallibriLeadRepositoryInterface;
use Src\Domain\Projects\ProjectPlanValueRepositoryInterface;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Serp\SerpPositionRepositoryInterface;
use Src\Domain\Users\UserRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaGoalConversion;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
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
        AgencyRepositoryInterface $agencyRepository
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
    }

    public function getData(int $projectId, DateTimeRange $period): ReportData
    {
        // Common
        $currentYear = date('Y');
        $builder = ReportData::builder()
            ->value('current_year', $currentYear)
            ->value('period_from', $period->start->format('d.m.Y'))
            ->value('period_to', $period->end->format('d.m.Y'));

        // Project
        $project = $this->projectRepository->findById($projectId);
        $builder->value('project_domain', $project->getDomain())
            ->value('project_name', $project->getName());

        // Manager
        $client = $this->clientRepository->findById($project->getClientId());
        $manager = $this->userRepository->findById($client->getManagerId());
        $builder->value('manager_last_name', $manager->getLastName())
            ->value('manager_first_name', $manager->getFirstName())
            ->value('manager_phone', $manager->getPhone())
            ->value('manager_email', $manager->getEmail())
            ->image('manager_photo', !empty($manager->getImagePath()) ? Storage::disk('public')->path($manager->getImagePath()) : '');

        // Agency
        $agencyId = empty($manager->getAgencysIds()) ? null : $manager->getAgencysIds()[0];
        if ($agencyId === null) {
            $builder->value('agency_address', '')
                ->value('agency_domain', '')
                ->image('agency_image', '')
                ->value('agency_email', '')
                ->value('agency_phone', '');
        } else {
            $agency = $this->agencyRepository->findById($agencyId);
            $builder->value('agency_address', $agency->getAddress())
                ->value('agency_domain', $agency->getDomain())
                ->image('agency_image', !empty($agency->getLogoPath()) ? Storage::disk('public')->path($agency->getLogoPath()) : '')
                ->value('agency_email', $agency->getEmail())
                ->value('agency_phone', $agency->getPhone());
        }

        // Callibri
        $callibriLeads = $this->callibriLeadRepository->findByProjectId($projectId, $period);
        $callibriTableHeaders = [
            'Дата',
            'Класс',
            'Тип обращения',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term'
        ];
        $callibriTableRows = array_map(fn($lead) => [
            $lead->getDate()->format('d.m.Y'),
            '',
            '',
            $lead->getUtmSource(),
            $lead->getUtmMedium(),
            $lead->getUtmCampaign(),
            $lead->getUtmContent(),
            $lead->getUtmTerm()
        ], $callibriLeads);
        $builder->table('callibri.table', $callibriTableHeaders, $callibriTableRows);

        // Yandex search top percentages
        $percentages = $this->serpPositionRepository->getTopPercentages($projectId, $period, 'yandex');
        $builder->value('yandex_search.top_3', $percentages['top_3'] . '%')
            ->value('yandex_search.top_5', $percentages['top_5'] . '%')
            ->value('yandex_search.top_10', $percentages['top_10'] . '%');

        // Yandex search positions table
        $keywordPositions = $this->serpPositionRepository->getKeywordPositions($projectId, $period, 'yandex');
        $yandexSearchTableRows = array_map(
            fn($item) => [$item['phrase'], $item['position'] ?? '—'],
            $keywordPositions
        );
        $builder->table(
            'yandex_search.table',
            ['Фраза', 'Позиция'],
            $yandexSearchTableRows
        );

        // Yandex Direct
        $yandexDirectStats = $this->yandexDirectRepository->findByProjectId($projectId, $period);
        $yandexDirectTableRows = array_map(
            fn($stats) => [
                $stats->getCampaignName(),
                $stats->getImpressions(),
                $stats->getClicks(),
                $stats->getCostWithVat(),
                $stats->getCostWithoutVat(),
                $stats->getCtr() . '%',
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
            'Название цели'
        ], $yandexDirectTableRows);

        // Yandex Metrika - Goals by search engines
        $searchEnginesStats = $this->yandexMetrikaRepository->getSearchEnginesStats($projectId, $period);
        $searchEnginesTable = $this->buildSearchEnginesTable($searchEnginesStats, $period);
        $builder->table('ym.table.goals_search_engines', $searchEnginesTable['headers'], $searchEnginesTable['rows']);

        $yandexTable = $this->buildSingleSearchEngineTable($searchEnginesStats, $period, 'yandex');
        $builder->table('ym.table.goals_search_engines.yandex', $yandexTable['headers'], $yandexTable['rows']);

        $googleTable = $this->buildSingleSearchEngineTable($searchEnginesStats, $period, 'google');
        $builder->table('ym.table.goals_search_engines.google', $googleTable['headers'], $googleTable['rows']);

        // Yandex Metrika - Goals UTM
        $goalsUtmStats = $this->yandexMetrikaRepository->getGoalUtmStats($projectId, $period);
        $goalsUtmTable = $this->buildGoalsUtmTable($goalsUtmStats);
        $builder->table('ym.table.goals_utm', $goalsUtmTable['headers'], $goalsUtmTable['rows']);

        // Yandex Metrika - Goals Conversions
        $goalConversionsStats = $this->yandexMetrikaRepository->getGoalConversionsStats($projectId, $period);
        $goalsConversionsTable = $this->buildGoalsConversionsTable($goalConversionsStats, $period);
        $builder->table('ym.table.goals_conversions', $goalsConversionsTable['headers'], $goalsConversionsTable['rows']);

        // Yandex Metrika - Visits from search systems (Plan/Fact)
        $visitsSearchSystemsTable = $this->buildVisitsSearchSystemsTable($projectId, $searchEnginesStats, $period);
        $builder->table('ym.table.visits_search_systems', $visitsSearchSystemsTable['headers'], $visitsSearchSystemsTable['rows']);

        // Yandex Metrika - Visits by geography
        $visitsGeoStats = $this->yandexMetrikaRepository->getVisitsGeoStats($projectId, $period);
        $visitsGeoTable = $this->buildVisitsGeoTable($visitsGeoStats);
        $builder->table('ym.table.visits_geo', $visitsGeoTable['headers'], $visitsGeoTable['rows']);

        // Yandex Metrika - Visits by search queries
        $visitsSearchQueriesStats = $this->yandexMetrikaRepository->getVisitsSearchQueriesStats($projectId, $period);
        $visitsSearchQueriesTable = $this->buildVisitsSearchQueriesTable($visitsSearchQueriesStats);
        $builder->table('ym.table.visits_search_queries', $visitsSearchQueriesTable['headers'], $visitsSearchQueriesTable['rows']);

        // Completed works
        $completedWorks = $this->completedWorkRepository->findByProjectId($projectId, $period);
        $completedWorksList = array_map(
            fn($work) => $work->getTitle(),
            $completedWorks
        );
        $builder->list('megaplan.list', $completedWorksList);

        $utmCampaignMatchTable = $this->buildUtmCampaignMatchTable(
            $yandexDirectStats,
            $goalsUtmStats,
            $callibriLeads
        );
        $builder->table('yd.table.utm_campaign_match', $utmCampaignMatchTable['headers'], $utmCampaignMatchTable['rows']);

        return $builder->build();
    }

    /**
     * Строит таблицу статистики по поисковым системам.
     *
     * @param array<\Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats> $stats
     * @param \Src\Domain\ValueObjects\DateTimeRange $period
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

            if (!isset($data[$engine])) {
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
     * @param array<\Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats> $stats
     * @param \Src\Domain\ValueObjects\DateTimeRange $period
     * @param string $engineName Имя поисковой системы (yandex, google)
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
        $filteredStats = array_filter($stats, fn($item) => $item->getSearchEngine() === $engineName);

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

        return $months[(int) $month->format('n')] . ' ' . $month->format('Y');
    }

    /**
     * Строит таблицу достижений целей с UTM-метками.
     *
     * @param array<YandexMetrikaGoalUtm> $stats
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

            if (!isset($aggregated[$goalName])) {
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
     * @param array<YandexMetrikaGoalConversion> $stats
     * @param DateTimeRange $period
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

            if (!isset($data[$goalName])) {
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
     * Строит таблицу "Переходы из поисковых систем" с планом и фактом.
     *
     * @param int $projectId
     * @param array<\Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats> $stats
     * @param DateTimeRange $period
     * @return array{headers: array<string>, rows: array<array<string|int|float|null>>}
     */
    private function buildVisitsSearchSystemsTable(int $projectId, array $stats, DateTimeRange $period): array
    {
        $headers = ['Название', 'План', 'Факт'];

        // Получаем плановое значение для общего объёма визитов
        $planValues = $this->projectPlanValueRepository->findByCodes(
            $projectId,
            ['visits'],
            $period
        );

        $totalPlan = isset($planValues['visits']) && $planValues['visits'] !== null
            ? (int) $planValues['visits']->getValue()
            : null;

        // Группируем фактические данные по поисковым системам
        $factData = [];
        foreach ($stats as $item) {
            $engine = $item->getSearchEngine();
            if (!isset($factData[$engine])) {
                $factData[$engine] = 0;
            }
            $factData[$engine] += $item->getVisits();
        }

        // Метки поисковых систем
        $engineLabels = [
            'yandex' => 'Яндекс',
            'google' => 'Google',
            'other' => 'другие',
        ];

        // Формируем строки таблицы (только факт, план только в итоговой строке)
        $rows = [];
        $totalFact = 0;

        foreach (['yandex', 'google', 'other'] as $engine) {
            $factValue = $factData[$engine] ?? 0;
            $totalFact += $factValue;

            $rows[] = [
                $engineLabels[$engine],
                '—',
                $factValue,
            ];
        }

        // Строка "Итого" — здесь показываем общий план
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
     * @param array<YandexMetrikaVisitsGeo> $stats
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

            if (!isset($aggregated[$city])) {
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
        uasort($aggregated, fn($a, $b) => $b['visits'] <=> $a['visits']);

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
     * @param array<YandexMetrikaVisitsSearchQueries> $stats
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

            if (!isset($aggregated[$phrase])) {
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
        uasort($aggregated, fn($a, $b) => $b['visits'] <=> $a['visits']);

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
     * @param array<YandexDirectCampaignStats> $yandexDirectStats
     * @param array<YandexMetrikaGoalUtm> $goalsUtmStats
     * @param array<\Src\Domain\Leads\CallibriLead> $callibriLeads
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
                if (!isset($metrikaConversionsByCampaign[$utmCampaign])) {
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
                if (!isset($callibriLeadsByCampaign[$utmCampaign])) {
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
                $stats->getCtr() . '%',
                $stats->getCpc(),
                $conversions,
                $cpl,
                $stats->getGoalName() ?? '—',
            ];
        }

        // Добавляем строки для нераспределённых конверсий из Метрики
        foreach ($metrikaConversionsByCampaign as $utmCampaign => $count) {
            if (!isset($usedMetrikaCampaigns[$utmCampaign])) {
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
            if (!isset($usedCallibriCampaigns[$utmCampaign])) {
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
