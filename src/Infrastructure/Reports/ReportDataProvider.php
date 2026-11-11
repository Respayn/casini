<?php

namespace Src\Infrastructure\Reports;

use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Data\ProjectUtmMappingData;
use App\Enums\UtmType;
use App\Repositories\Interfaces\ProjectUtmMappingRepositoryInterface;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\ProjectFacts\ProjectKpiFactsService;
use Illuminate\Support\Carbon;
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
use Src\Domain\Reports\ReportColumnGrouping;
use Src\Domain\Reports\ReportDateSort;
use Src\Domain\Reports\ReportPeriodBuckets;
use Src\Domain\Serp\SerpPositionRepositoryInterface;
use Src\Domain\Users\UserRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaGoalConversion;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectSummary;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaSearchEngineGoal;
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

    private readonly ProjectUtmMappingRepositoryInterface $projectUtmMappingRepository;

    private readonly ProjectKpiFactsService $projectKpiFacts;

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
        ProjectUtmMappingRepositoryInterface $projectUtmMappingRepository,
        ProjectKpiFactsService $projectKpiFacts,
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
        $this->projectUtmMappingRepository = $projectUtmMappingRepository;
        $this->integrationProjectCredentials = $integrationProjectCredentials;
        $this->projectKpiFacts = $projectKpiFacts;
    }

    public function getData(
        int $projectId,
        DateTimeRange $period,
        ReportColumnGrouping $columnGrouping = ReportColumnGrouping::Months,
        ReportDateSort $dateSort = ReportDateSort::NewestFirst,
    ): ReportData {
        $buckets = ReportPeriodBuckets::make($period, $columnGrouping, $dateSort);
        $periodSeries = [];

        $builder = ReportData::builder()
            ->value('current_year', date('Y'))
            ->value('period_from', $period->start->format('d.m.Y'))
            ->value('period_to', $period->end->format('d.m.Y'))
            ->value('period_from_text', $period->startDateLabel())
            ->value('period_to_text', $period->endDateLabel())
            ->value('period_month', $buckets->reportingMonth->monthYearLabel());

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

        $this->safely($builder, 'Данные позиций Яндекс не загружены', function () use ($builder, $projectId, $period, $buckets, &$periodSeries): void {
            $settings = $this->integrationProjectCredentials->settingsFor($projectId, 'yandex_search_api') ?? [];
            $regions = is_array($settings['regions'] ?? null) ? $settings['regions'] : [];

            foreach (YandexSearchIndexedTags::numberedRegions($regions) as $region) {
                $index = $region['index'];
                $builder->value(YandexSearchIndexedTags::key('region', $index), $region['label']);

                $monthlyPositions = $this->serpPositionRepository->getKeywordPositionsByMonth(
                    $projectId,
                    $period,
                    'yandex',
                    $region['code'],
                );
                $keywordPositions = YandexSearchIndexedTags::keywordRowsOrSettings(
                    $this->serpPositionRepository->getKeywordPositions(
                        $projectId,
                        $period,
                        'yandex',
                        $region['code'],
                    ),
                    $region['phrases'],
                );
                $positionValues = array_column($keywordPositions, 'position');
                $shares = YandexSearchIndexedTags::topShares($positionValues);
                $hasNumericRank = YandexSearchIndexedTags::hasNumericRank($positionValues);
                $builder->value(YandexSearchIndexedTags::key('top_3', $index), YandexSearchIndexedTags::formatShare($shares['top_3'], $hasNumericRank))
                    ->value(YandexSearchIndexedTags::key('top_5', $index), YandexSearchIndexedTags::formatShare($shares['top_5'], $hasNumericRank))
                    ->value(YandexSearchIndexedTags::key('top_10', $index), YandexSearchIndexedTags::formatShare($shares['top_10'], $hasNumericRank));

                $periodSeries[YandexSearchIndexedTags::key('top_3', $index)] = $this->topShareSeries($monthlyPositions, $buckets, 3);
                $periodSeries[YandexSearchIndexedTags::key('top_5', $index)] = $this->topShareSeries($monthlyPositions, $buckets, 5);
                $periodSeries[YandexSearchIndexedTags::key('top_10', $index)] = $this->topShareSeries($monthlyPositions, $buckets, 10);

                $positionsByPhrase = [];
                foreach ($monthlyPositions as $item) {
                    $positionsByPhrase[$item['phrase']][$item['month']] = $item['position'];
                }

                $pairRows = array_map(
                    function ($item) use ($positionsByPhrase, $buckets): array {
                        $phrase = $item['phrase'];
                        $monthly = $positionsByPhrase[$phrase] ?? [];

                        return [
                            'phrase' => $phrase,
                            'position' => (string) ($item['position'] ?? '-'),
                            'series' => $buckets->seriesFromMonthly($monthly, '-', false),
                        ];
                    },
                    $keywordPositions
                );
                $builder->keywordPositionPair(
                    YandexSearchIndexedTags::key('phrase', $index),
                    YandexSearchIndexedTags::key('position', $index),
                    $pairRows,
                );
            }

            // % в топ-10 как в Статистике: среднее дневных снимков Search API.
            $periodStart = $period->getStart();
            $periodEnd = $period->getEnd();
            if ($periodStart !== null && $periodEnd !== null) {
                $from = Carbon::instance($periodStart)->startOfDay();
                $to = Carbon::instance($periodEnd)->startOfDay();
                $top10Average = $this->projectKpiFacts->top10Average($projectId, $from, $to);
                if ($top10Average !== null) {
                    foreach (YandexSearchIndexedTags::numberedRegions($regions) as $region) {
                        $index = $region['index'];
                        $builder->value(
                            YandexSearchIndexedTags::key('top_10', $index),
                            YandexSearchIndexedTags::formatShare($top10Average, true),
                        );
                        $periodSeries[YandexSearchIndexedTags::key('top_10', $index)] = $buckets->seriesFromMonthly(
                            $this->top10MonthlyFromSearchApi($projectId, $buckets),
                            '-',
                            false,
                        );
                    }
                }
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
                    $stats->getGoalName() ?? '-',
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

        $this->safely($builder, 'Данные целей Яндекс.Метрики не загружены', function () use ($builder, $projectId, $period, $buckets, &$searchEnginesStats, &$periodSeries): void {
            $searchEnginesStats = $this->yandexMetrikaRepository->getSearchEnginesStats($projectId, $period);
            $searchEnginesTable = $this->buildSearchEnginesTable($searchEnginesStats, $buckets);
            $builder->table('ym.table.goals_search_engines', $searchEnginesTable['headers'], $searchEnginesTable['rows']);

            $yandexTable = $this->buildSingleSearchEngineTable($searchEnginesStats, $buckets, 'yandex');
            $builder->table('ym.table.goals_search_engines.yandex', $yandexTable['headers'], $yandexTable['rows']);

            $googleTable = $this->buildSingleSearchEngineTable($searchEnginesStats, $buckets, 'google');
            $builder->table('ym.table.goals_search_engines.google', $googleTable['headers'], $googleTable['rows']);

            $settings = $this->integrationProjectCredentials->settingsFor($projectId, 'yandex_metrika') ?? [];
            $selectedGoalIds = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []);
            $goalStats = $this->yandexMetrikaRepository->getSearchEngineGoals($projectId, $period);

            $builder->keywordPositionPair(
                YandexMetrikaSearchEngineGoals::YANDEX_NAME_KEY,
                YandexMetrikaSearchEngineGoals::YANDEX_VALUE_KEY,
                $this->withMonthlySeries(
                    YandexMetrikaSearchEngineGoals::pairRows($goalStats, 'yandex', $selectedGoalIds),
                    $this->goalMonthlyByName($goalStats, 'yandex', $selectedGoalIds),
                    $buckets,
                    '0',
                    true,
                ),
            );
            $builder->value(
                YandexMetrikaSearchEngineGoals::YANDEX_TOTAL_KEY,
                (string) YandexMetrikaSearchEngineGoals::total($goalStats, 'yandex', $selectedGoalIds),
            );
            $periodSeries[YandexMetrikaSearchEngineGoals::YANDEX_TOTAL_KEY] = $buckets->seriesFromMonthly(
                $this->goalMonthlyTotals($goalStats, 'yandex', $selectedGoalIds),
                '0',
                true,
            );
            $builder->keywordPositionPair(
                YandexMetrikaSearchEngineGoals::GOOGLE_NAME_KEY,
                YandexMetrikaSearchEngineGoals::GOOGLE_VALUE_KEY,
                $this->withMonthlySeries(
                    YandexMetrikaSearchEngineGoals::pairRows($goalStats, 'google', $selectedGoalIds),
                    $this->goalMonthlyByName($goalStats, 'google', $selectedGoalIds),
                    $buckets,
                    '0',
                    true,
                ),
            );
            $builder->value(
                YandexMetrikaSearchEngineGoals::GOOGLE_TOTAL_KEY,
                (string) YandexMetrikaSearchEngineGoals::total($goalStats, 'google', $selectedGoalIds),
            );
            $periodSeries[YandexMetrikaSearchEngineGoals::GOOGLE_TOTAL_KEY] = $buckets->seriesFromMonthly(
                $this->goalMonthlyTotals($goalStats, 'google', $selectedGoalIds),
                '0',
                true,
            );
        });

        $this->safely($builder, 'Данные UTM-целей Яндекс.Метрики не загружены', function () use ($builder, $projectId, $period, &$goalsUtmStats): void {
            $goalsUtmStats = $this->yandexMetrikaRepository->getGoalUtmStats($projectId, $period);
            $goalsUtmTable = $this->buildGoalsUtmTable($goalsUtmStats);
            $builder->table('ym.table.goals_utm', $goalsUtmTable['headers'], $goalsUtmTable['rows']);
        });

        $this->safely($builder, 'Данные конверсий Яндекс.Метрики не загружены', function () use ($builder, $projectId, $period, $buckets): void {
            $goalConversionsStats = $this->yandexMetrikaRepository->getGoalConversionsStats($projectId, $period);
            $goalsConversionsTable = $this->buildGoalsConversionsTable($goalConversionsStats, $buckets);
            $builder->table('ym.table.goals_conversions', $goalsConversionsTable['headers'], $goalsConversionsTable['rows']);
        });

        $this->safely($builder, 'Сводка целей Директа не загружена', function () use ($builder, $projectId, $period, $buckets): void {
            $goalDirectSummaryStats = $this->yandexMetrikaRepository->getGoalDirectSummaryStats($projectId, $period);
            $goalsDirectSummaryTable = $this->buildGoalsDirectSummaryTable($goalDirectSummaryStats, $buckets);
            $builder->table('ym.table.goals_direct_summary', $goalsDirectSummaryTable['headers'], $goalsDirectSummaryTable['rows']);
        });

        $this->safely($builder, 'Таблица визитов из поиска не загружена', function () use ($builder, $projectId, $period, $searchEnginesStats): void {
            $visitsSearchSystemsTable = $this->buildVisitsSearchSystemsTable($projectId, $searchEnginesStats, $period);
            $builder->table('ym.table.visits_search_systems', $visitsSearchSystemsTable['headers'], $visitsSearchSystemsTable['rows']);
        });
        $planVisits = 0;
        $planByMonth = [];
        $this->safely($builder, 'План посещаемости не загружен', function () use ($projectId, $period, &$planVisits, &$planByMonth): void {
            $planVisits = $this->projectPlanValueRepository->sumByCode($projectId, 'visits', $period);
            $planByMonth = $this->planVisitsByMonth($projectId, $period);
        });
        $builder->values(YandexMetrikaSearchVisits::tagValues($searchEnginesStats, $planVisits));
        $visitsByEngine = $this->searchVisitsByEngineAndMonth($searchEnginesStats);
        $periodSeries['ym.search_visits.yandex'] = $buckets->seriesFromMonthly($visitsByEngine['yandex'], '0', true);
        $periodSeries['ym.search_visits.google'] = $buckets->seriesFromMonthly($visitsByEngine['google'], '0', true);
        $periodSeries['ym.search_visits.other'] = $buckets->seriesFromMonthly($visitsByEngine['other'], '0', true);
        $periodSeries['ym.search_visits.total'] = $buckets->seriesFromMonthly($visitsByEngine['total'], '0', true);
        $periodSeries['ym.search_visits.plan'] = $buckets->seriesFromMonthly($planByMonth, '0', true);
        $periodSeries['ym.search_visits.fact'] = $periodSeries['ym.search_visits.total'];
        $periodSeries['ym.search_visits.performance'] = $buckets->seriesFromMonthly(
            $this->performanceByMonth($visitsByEngine['total'], $planByMonth),
            '',
            false,
        );

        $this->safely($builder, 'Таблица визитов по географии не загружена', function () use ($builder, $projectId, $period, $buckets): void {
            $visitsGeoStats = $this->yandexMetrikaRepository->getVisitsGeoStats($projectId, $period);
            $visitsGeoTable = $this->buildVisitsGeoTable($visitsGeoStats);
            $builder->table('ym.table.visits_geo', $visitsGeoTable['headers'], $visitsGeoTable['rows']);

            $settings = $this->integrationProjectCredentials->settingsFor($projectId, 'yandex_metrika') ?? [];
            $useVisitors = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($settings['visits_metric'] ?? null)
                === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS;
            $builder->keywordPositionPair(
                YandexMetrikaGeoVisits::CITY_KEY,
                YandexMetrikaGeoVisits::VISITS_KEY,
                $this->withMonthlySeries(
                    YandexMetrikaGeoVisits::pairRows($visitsGeoStats, $useVisitors),
                    $this->geoMonthlyByCity($visitsGeoStats, $useVisitors),
                    $buckets,
                    '0',
                    true,
                ),
            );
        });

        $this->safely($builder, 'Таблица визитов по запросам не загружена', function () use ($builder, $projectId, $period, $buckets): void {
            $visitsSearchQueriesStats = $this->yandexMetrikaRepository->getVisitsSearchQueriesStats($projectId, $period);
            $visitsSearchQueriesTable = $this->buildVisitsSearchQueriesTable($visitsSearchQueriesStats);
            $builder->table('ym.table.visits_search_queries', $visitsSearchQueriesTable['headers'], $visitsSearchQueriesTable['rows']);

            $settings = $this->integrationProjectCredentials->settingsFor($projectId, 'yandex_metrika') ?? [];
            $useVisitors = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($settings['visits_metric'] ?? null)
                === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS;
            $builder->keywordPositionPair(
                YandexMetrikaSearchQueries::PHRASE_KEY,
                YandexMetrikaSearchQueries::VISITS_KEY,
                $this->withMonthlySeries(
                    YandexMetrikaSearchQueries::pairRows($visitsSearchQueriesStats, $useVisitors),
                    $this->queryMonthlyByPhrase($visitsSearchQueriesStats, $useVisitors),
                    $buckets,
                    '0',
                    true,
                ),
            );
        });

        $this->safely($builder, 'Список выполненных работ не загружен', function () use ($builder, $projectId, $buckets): void {
            $completedWorks = $this->completedWorkRepository->findByProjectId($projectId, $buckets->reportingMonth);
            $completedWorksList = array_map(
                fn ($work) => str_repeat("\t", $work->getDepth()).$work->getTitle(),
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

        $utmCampaignLabels = $this->utmCampaignLabelsForProject($projectId);

        $this->safely($builder, 'Общая статистика Директа не загружена', function () use (
            $builder,
            $projectId,
            $period,
            $yandexDirectStats,
            $utmCampaignLabels,
        ): void {
            $includeCallibri = $this->integrationProjectCredentials->hasEnabled($projectId, 'callibri');
            $includeMetrika = $this->integrationProjectCredentials->hasEnabled($projectId, 'yandex_metrika');
            $periodStart = $period->getStart();
            $periodEnd = $period->getEnd();
            if ($periodStart === null || $periodEnd === null) {
                return;
            }
            $from = Carbon::instance($periodStart)->startOfDay();
            $to = Carbon::instance($periodEnd)->startOfDay();

            $filteredCallibri = $includeCallibri
                ? array_map(
                    static fn ($lead) => CallibriLead::restore([
                        'id' => $lead->id,
                        'project_id' => $lead->project_id,
                        'external_id' => $lead->external_id,
                        'date' => (string) $lead->date,
                        'utm_source' => $lead->utm_source,
                        'utm_campaign' => $lead->utm_campaign,
                        'utm_medium' => $lead->utm_medium,
                        'utm_content' => $lead->utm_content,
                        'utm_term' => $lead->utm_term,
                    ]),
                    $this->projectKpiFacts->filteredCallibriLeads($projectId, $from, $to),
                )
                : [];

            $metrikaSettings = $includeMetrika
                ? ($this->integrationProjectCredentials->settingsFor($projectId, 'yandex_metrika') ?? [])
                : [];
            $countSource = $includeMetrika
                ? YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($metrikaSettings)
                : null;

            $filteredMetrika = ($includeMetrika
                && $countSource === YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_UTM)
                ? array_map(
                    static fn ($goal) => YandexMetrikaGoalUtm::restore([
                        'id' => $goal->id,
                        'project_id' => $goal->project_id,
                        'goal_name' => $goal->goal_name,
                        'achieved_date' => $goal->achieved_date instanceof \DateTimeInterface
                            ? $goal->achieved_date->format('Y-m-d')
                            : (string) $goal->achieved_date,
                        'utm_source' => $goal->utm_source,
                        'utm_medium' => $goal->utm_medium,
                        'utm_campaign' => $goal->utm_campaign,
                        'utm_content' => $goal->utm_content,
                        'utm_term' => $goal->utm_term,
                    ]),
                    $this->projectKpiFacts->filteredMetrikaUtmGoals($projectId, $from, $to),
                )
                : [];

            $goalDirectCampaigns = [];
            $metrikaGoalsTotal = null;
            if ($includeMetrika
                && $countSource === YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_DIRECT_SUMMARY
            ) {
                $goalDirectCampaigns = $this->yandexMetrikaRepository->getGoalDirectCampaignStats($projectId, $period);
                if ($goalDirectCampaigns === []) {
                    $metrikaGoalsTotal = $this->projectKpiFacts->directSummaryGoalsTotal($projectId, $from, $to);
                }
            }

            $budgetTotal = $this->projectKpiFacts->directBudget($projectId, $from, $to, true);

            $generalStats = YandexDirectGeneralStats::build(
                $yandexDirectStats,
                $filteredCallibri,
                $filteredMetrika,
                $includeCallibri,
                $includeMetrika,
                $utmCampaignLabels,
                $budgetTotal,
                $metrikaGoalsTotal,
                $goalDirectCampaigns,
            );
            $builder->directCampaignStatRows($generalStats['rows']);
            $builder->values($generalStats['totals']);
        });

        $this->safely($builder, 'Статистика по обращениям не загружена', function () use (
            $builder,
            $projectId,
            $period,
            $yandexDirectStats,
            $utmCampaignLabels,
        ): void {
            $periodStart = $period->getStart();
            $periodEnd = $period->getEnd();
            if ($periodStart === null || $periodEnd === null) {
                return;
            }

            $from = Carbon::instance($periodStart)->startOfDay();
            $to = Carbon::instance($periodEnd)->startOfDay();
            $metrikaSettings = $this->integrationProjectCredentials->settingsFor($projectId, 'yandex_metrika') ?? [];
            $countSource = YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($metrikaSettings);

            if ($countSource === YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_UTM) {
                $goalsUtm = array_map(
                    static fn ($goal) => YandexMetrikaGoalUtm::restore([
                        'id' => $goal->id,
                        'project_id' => $goal->project_id,
                        'goal_name' => $goal->goal_name,
                        'achieved_date' => $goal->achieved_date instanceof \DateTimeInterface
                            ? $goal->achieved_date->format('Y-m-d')
                            : (string) $goal->achieved_date,
                        'utm_source' => $goal->utm_source,
                        'utm_medium' => $goal->utm_medium,
                        'utm_campaign' => $goal->utm_campaign,
                        'utm_content' => $goal->utm_content,
                        'utm_term' => $goal->utm_term,
                    ]),
                    $this->projectKpiFacts->filteredMetrikaUtmGoals($projectId, $from, $to),
                );
                $appeals = YandexDirectAppealsStats::buildFromUtmGoals(
                    $yandexDirectStats,
                    $goalsUtm,
                    $utmCampaignLabels,
                );
            } else {
                $goalCampaignStats = $this->yandexMetrikaRepository->getGoalDirectCampaignStats($projectId, $period);
                $appeals = YandexDirectAppealsStats::build(
                    $yandexDirectStats,
                    $goalCampaignStats,
                    $utmCampaignLabels,
                );
            }

            $builder->directAppealsStats($appeals);
        });

        if ($buckets->expandColumns) {
            $builder->periodColumns($buckets->labels(), $periodSeries);
        }

        return $builder->build();
    }

    /**
     * Средний % в топ-10 Search API по месяцам периода (ключ Y-m).
     *
     * @return array<string, float>
     */
    private function top10MonthlyFromSearchApi(int $projectId, ReportPeriodBuckets $buckets): array
    {
        $monthly = [];
        foreach ($buckets->items as $bucket) {
            $current = Carbon::instance($bucket->start)->startOfMonth();
            $last = Carbon::instance($bucket->end)->startOfMonth();
            while ($current->lte($last)) {
                $monthKey = $current->format('Y-m');
                if (! isset($monthly[$monthKey])) {
                    $from = $current->copy()->startOfMonth();
                    $to = $current->copy()->endOfMonth()->startOfDay();
                    $avg = $this->projectKpiFacts->top10Average($projectId, $from, $to);
                    if ($avg !== null) {
                        $monthly[$monthKey] = $avg;
                    }
                }
                $current->addMonth();
            }
        }

        return $monthly;
    }

    /**
     * Подписи кампаний из блока подмены utm_campaign в клиенто-проекте.
     *
     * @return array<string, string> номер кампании => подпись
     */
    private function utmCampaignLabelsForProject(int $projectId): array
    {
        $labels = [];
        /** @var iterable<ProjectUtmMappingData> $mappings */
        $mappings = $this->projectUtmMappingRepository->findBy('project_id', $projectId);

        foreach ($mappings as $mapping) {
            if ($mapping->utm_type !== UtmType::UTM_CAMPAIGN) {
                continue;
            }

            $utmValue = trim($mapping->utm_value);
            $replacement = trim($mapping->replacement_value);
            if ($utmValue === '' || $replacement === '') {
                continue;
            }

            $labels[$utmValue] = $replacement;
        }

        return $labels;
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
    private function buildSearchEnginesTable(array $stats, ReportPeriodBuckets $buckets): array
    {
        $data = [];
        foreach ($stats as $item) {
            $engine = $item->getSearchEngine();
            $monthKey = $item->getMonth()->format('Y-m');
            $data[$engine][$monthKey] = ($data[$engine][$monthKey] ?? 0) + $item->getConversions();
        }

        $engineLabels = [
            'yandex' => 'Яндекс',
            'google' => 'Google',
            'other' => 'другие',
        ];

        return [
            'headers' => $this->datedHeaders('Поисковая система', $buckets),
            'rows' => $this->datedNamedRows($data, $buckets, $engineLabels, true),
        ];
    }

    /**
     * Строит таблицу статистики для конкретной поисковой системы.
     *
     * @param  array<YandexMetrikaSearchEnginesStats>  $stats
     * @param  string  $engineName  Имя поисковой системы (yandex, google)
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildSingleSearchEngineTable(array $stats, ReportPeriodBuckets $buckets, string $engineName): array
    {
        $conversionsByMonth = [];
        $visitsByMonth = [];
        foreach ($stats as $item) {
            if ($item->getSearchEngine() !== $engineName) {
                continue;
            }

            $monthKey = $item->getMonth()->format('Y-m');
            $conversionsByMonth[$monthKey] = ($conversionsByMonth[$monthKey] ?? 0) + $item->getConversions();
            $visitsByMonth[$monthKey] = ($visitsByMonth[$monthKey] ?? 0) + $item->getVisits();
        }

        return [
            'headers' => $this->datedHeaders('Показатель', $buckets),
            'rows' => [
                $this->datedMetricRow('Конверсии', $conversionsByMonth, $buckets),
                $this->datedMetricRow('Визиты', $visitsByMonth, $buckets),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function datedHeaders(string $firstColumn, ReportPeriodBuckets $buckets): array
    {
        return array_merge([$firstColumn], $buckets->labels());
    }

    /**
     * @param  array<string, int|float|string|null>  $valuesByMonth
     * @return list<string|int>
     */
    private function datedMetricRow(string $label, array $valuesByMonth, ReportPeriodBuckets $buckets): array
    {
        $series = $buckets->seriesFromMonthly($valuesByMonth, '0', true);

        return array_merge([$label], $this->numericSeries($series));
    }

    /**
     * @param  array<string, array<string, int|float|string|null>>  $byNameAndMonth
     * @param  array<string, string>  $forcedNames  ключ ряда => подпись; пусто = все ключи данных
     * @return list<array<string|int>>
     */
    private function datedNamedRows(
        array $byNameAndMonth,
        ReportPeriodBuckets $buckets,
        array $forcedNames = [],
        bool $withTotalRow = true,
    ): array {
        $names = $forcedNames !== [] ? $forcedNames : array_keys($byNameAndMonth);
        $columnCount = count($buckets->items);
        $columnTotals = array_fill(0, $columnCount, 0);
        $rows = [];

        foreach ($names as $key => $label) {
            $name = is_string($key) ? $key : (string) $label;
            $caption = is_string($key) ? $label : $name;
            $series = $buckets->seriesFromMonthly($byNameAndMonth[$name] ?? [], '0', true);
            $rows[] = array_merge([$caption], $this->numericSeries($series));

            foreach ($buckets->items as $index => $_) {
                $columnTotals[$index] += (int) $series[$index];
            }
        }

        if ($withTotalRow) {
            $rows[] = array_merge(['Итого'], $columnTotals);
        }

        return $rows;
    }

    /**
     * @param  list<string>  $series
     * @return list<string|int>
     */
    private function numericSeries(array $series): array
    {
        return array_map(
            static fn (string $value): int|string => is_numeric($value) ? (int) $value : $value,
            $series,
        );
    }

    /**
     * @param  list<array{phrase: string, position: string, series?: list<string>}>  $pairRows
     * @param  array<string, array<string, int|float|string|null>>  $monthlyByName
     * @return list<array{phrase: string, position: string, series: list<string>}>
     */
    private function withMonthlySeries(
        array $pairRows,
        array $monthlyByName,
        ReportPeriodBuckets $buckets,
        string $empty,
        bool $sumTotal,
    ): array {
        return array_map(
            static fn (array $row): array => [
                'phrase' => $row['phrase'],
                'position' => $row['position'],
                'series' => $buckets->seriesFromMonthly($monthlyByName[$row['phrase']] ?? [], $empty, $sumTotal),
            ],
            $pairRows,
        );
    }

    /**
     * @param  list<array{phrase: string, position: int|null, month: string}>  $monthlyPositions
     * @return list<string>
     */
    private function topShareSeries(array $monthlyPositions, ReportPeriodBuckets $buckets, int $limit): array
    {
        $positionsByMonth = [];
        foreach ($monthlyPositions as $item) {
            $positionsByMonth[$item['month']][] = $item['position'];
        }

        $shareKey = match ($limit) {
            3 => 'top_3',
            5 => 'top_5',
            default => 'top_10',
        };

        $shareByMonth = [];
        foreach ($positionsByMonth as $month => $positions) {
            $shares = YandexSearchIndexedTags::topShares($positions);
            $shareByMonth[$month] = YandexSearchIndexedTags::formatShare(
                $shares[$shareKey],
                YandexSearchIndexedTags::hasNumericRank($positions),
            );
        }

        return $buckets->seriesFromMonthly($shareByMonth, '-', false);
    }

    /**
     * @param  list<YandexMetrikaSearchEngineGoal>  $stats
     * @param  list<int>  $selectedGoalIds
     * @return array<string, array<string, int>>
     */
    private function goalMonthlyByName(array $stats, string $engine, array $selectedGoalIds): array
    {
        $selected = array_fill_keys($selectedGoalIds, true);
        $monthly = [];

        foreach ($stats as $item) {
            if ($item->getSearchEngine() !== $engine || ! isset($selected[$item->getGoalId()])) {
                continue;
            }

            $name = trim($item->getGoalName());
            if ($name === '') {
                $name = 'Цель '.$item->getGoalId();
            }

            $month = $item->getMonth()->format('Y-m');
            $monthly[$name][$month] = ($monthly[$name][$month] ?? 0) + $item->getConversions();
        }

        return $monthly;
    }

    /**
     * @param  list<YandexMetrikaSearchEngineGoal>  $stats
     * @param  list<int>  $selectedGoalIds
     * @return array<string, int>
     */
    private function goalMonthlyTotals(array $stats, string $engine, array $selectedGoalIds): array
    {
        $selected = array_fill_keys($selectedGoalIds, true);
        $monthly = [];

        foreach ($stats as $item) {
            if ($item->getSearchEngine() !== $engine || ! isset($selected[$item->getGoalId()])) {
                continue;
            }

            $month = $item->getMonth()->format('Y-m');
            $monthly[$month] = ($monthly[$month] ?? 0) + $item->getConversions();
        }

        return $monthly;
    }

    /**
     * @param  list<YandexMetrikaSearchEnginesStats>  $stats
     * @return array{yandex: array<string, int>, google: array<string, int>, other: array<string, int>, total: array<string, int>}
     */
    private function searchVisitsByEngineAndMonth(array $stats): array
    {
        $result = [
            'yandex' => [],
            'google' => [],
            'other' => [],
            'total' => [],
        ];

        foreach ($stats as $item) {
            $visits = $item->getVisits();
            if ($visits <= 0) {
                continue;
            }

            $month = $item->getMonth()->format('Y-m');
            $engine = match ($item->getSearchEngine()) {
                'yandex' => 'yandex',
                'google' => 'google',
                default => 'other',
            };
            $result[$engine][$month] = ($result[$engine][$month] ?? 0) + $visits;
            $result['total'][$month] = ($result['total'][$month] ?? 0) + $visits;
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function planVisitsByMonth(int $projectId, DateTimeRange $period): array
    {
        if ($period->start === null || $period->end === null) {
            return [];
        }

        $result = [];
        $current = $period->start->modify('first day of this month')->setTime(0, 0);
        $end = $period->end->modify('first day of this month')->setTime(0, 0);

        while ($current <= $end) {
            $monthPeriod = new DateTimeRange(
                $current,
                $current->modify('last day of this month')->setTime(23, 59, 59),
            );
            $result[$current->format('Y-m')] = $this->projectPlanValueRepository->sumByCode($projectId, 'visits', $monthPeriod);
            $current = $current->modify('+1 month');
        }

        return $result;
    }

    /**
     * @param  array<string, int>  $factByMonth
     * @param  array<string, int>  $planByMonth
     * @return array<string, string>
     */
    private function performanceByMonth(array $factByMonth, array $planByMonth): array
    {
        $months = array_unique(array_merge(array_keys($factByMonth), array_keys($planByMonth)));
        $result = [];

        foreach ($months as $month) {
            $result[$month] = YandexMetrikaSearchVisits::performancePercent(
                (int) ($factByMonth[$month] ?? 0),
                (int) ($planByMonth[$month] ?? 0),
            );
        }

        return $result;
    }

    /**
     * @param  list<YandexMetrikaVisitsGeo>  $stats
     * @return array<string, array<string, int>>
     */
    private function geoMonthlyByCity(array $stats, bool $useVisitors): array
    {
        $monthly = [];
        foreach ($stats as $item) {
            $city = $item->getCity();
            if ($city === '') {
                continue;
            }

            $value = $useVisitors ? $item->getVisitors() : $item->getVisits();
            $month = $item->getMonth()->format('Y-m');
            $monthly[$city][$month] = ($monthly[$city][$month] ?? 0) + $value;
        }

        return $monthly;
    }

    /**
     * @param  list<YandexMetrikaVisitsSearchQueries>  $stats
     * @return array<string, array<string, int>>
     */
    private function queryMonthlyByPhrase(array $stats, bool $useVisitors): array
    {
        $monthly = [];
        foreach ($stats as $item) {
            $phrase = $item->getPhrase();
            if ($phrase === '') {
                continue;
            }

            $value = $useVisitors ? $item->getVisitors() : $item->getVisits();
            $month = $item->getMonth()->format('Y-m');
            $monthly[$phrase][$month] = ($monthly[$phrase][$month] ?? 0) + $value;
        }

        return $monthly;
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
    private function buildGoalsConversionsTable(array $stats, ReportPeriodBuckets $buckets): array
    {
        $data = [];
        foreach ($stats as $item) {
            $goalName = $item->getGoalName();
            $monthKey = $item->getMonth()->format('Y-m');
            $data[$goalName][$monthKey] = ($data[$goalName][$monthKey] ?? 0) + $item->getConversions();
        }

        return [
            'headers' => $this->datedHeaders('Название цели', $buckets),
            'rows' => $this->datedNamedRows($data, $buckets, [], true),
        ];
    }

    /**
     * Строит таблицу достижений целей из отчёта "Директ, сводка".
     *
     * @param  array<YandexMetrikaGoalDirectSummary>  $stats
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    private function buildGoalsDirectSummaryTable(array $stats, ReportPeriodBuckets $buckets): array
    {
        $data = [];
        foreach ($stats as $item) {
            $goalName = $item->getGoalName();
            $monthKey = $item->getMonth()->format('Y-m');
            $data[$goalName][$monthKey] = ($data[$goalName][$monthKey] ?? 0) + $item->getConversions();
        }

        return [
            'headers' => $this->datedHeaders('Название цели', $buckets),
            'rows' => $this->datedNamedRows($data, $buckets, [], true),
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
