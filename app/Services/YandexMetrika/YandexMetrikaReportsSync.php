<?php

namespace App\Services\YandexMetrika;

use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Enums\YandexMetrikaSearchEngine;
use App\Repositories\AgencyRepository;
use App\Repositories\IntegrationRepository;
use App\Services\IntegrationSync\YandexMetrikaSyncPeriod;
use App\Services\YandexMetrikaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Throwable;

/**
 * Съём отчётов Метрики в БД. Общий код для ночного collector и ручной команды metrika:sync.
 */
class YandexMetrikaReportsSync
{
    public const GOALS_SEARCH_ENGINES = 'goals_search_engines';

    public const GOALS_UTM = 'goals_utm';

    public const GOALS_CONVERSIONS = 'goals_conversions';

    public const GOALS_DIRECT_SUMMARY = 'goals_direct_summary';

    public const VISITS_SEARCH_ENGINES = 'visits_search_engines';

    public const VISITS_SEARCH_QUERIES = 'visits_search_queries';

    public const VISITS_GEO = 'visits_geo';

    public const REPORTS = [
        self::GOALS_SEARCH_ENGINES,
        self::GOALS_UTM,
        self::GOALS_CONVERSIONS,
        self::GOALS_DIRECT_SUMMARY,
        self::VISITS_SEARCH_ENGINES,
        self::VISITS_SEARCH_QUERIES,
        self::VISITS_GEO,
    ];

    private const GOAL_REPORTS = [
        self::GOALS_SEARCH_ENGINES,
        self::GOALS_UTM,
        self::GOALS_CONVERSIONS,
        self::GOALS_DIRECT_SUMMARY,
    ];

    private const UTM_DIMENSION_TO_FIELD = [
        'ym:s:UTMSource' => 'utm_source',
        'ym:s:UTMMedium' => 'utm_medium',
        'ym:s:UTMCampaign' => 'utm_campaign',
    ];

    public function __construct(
        private readonly YandexMetrikaService $metrikaService,
        private readonly YandexMetrikaRepositoryInterface $repository,
        private readonly IntegrationRepository $integrationRepository,
        private readonly AgencyRepository $agencyRepository,
        private readonly YandexMetrikaSyncPeriod $syncPeriod,
    ) {}

    /**
     * Включённые отчёты, для которых в settings хватает данных для запроса к API.
     *
     * @param  array<string, mixed>  $settings
     * @return list<string>
     */
    public function readyReports(array $settings): array
    {
        if (trim((string) ($settings['oauth_token'] ?? '')) === ''
            || (int) ($settings['counter_id'] ?? 0) <= 0
            || trim((string) ($settings['sync_enabled_at'] ?? '')) === ''
        ) {
            return [];
        }

        $reports = is_array($settings['reports'] ?? null) ? $settings['reports'] : [];
        $hasGoals = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []) !== [];

        return array_values(array_filter(
            self::REPORTS,
            fn (string $report) => ($reports[$report] ?? false)
                && ($hasGoals || ! in_array($report, self::GOAL_REPORTS, true)),
        ));
    }

    /**
     * Все проекты с включённым отчётом: период с месяца включения съёма по сегодня.
     *
     * @return array{synced: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function syncReportForAllProjects(string $report): array
    {
        $integrations = $this->integrationRepository->getEnabledProjectIntegrationsByCode('yandex_metrika');

        $stats = ['synced' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        foreach ($integrations as $integrationProject) {
            $settings = is_array($integrationProject->settings) ? $integrationProject->settings : [];

            if (! in_array($report, $this->readyReports($settings), true)) {
                $stats['skipped']++;

                continue;
            }

            $syncEnabledAt = (string) $settings['sync_enabled_at'];
            $period = $this->syncPeriod->range(Carbon::parse($syncEnabledAt), Carbon::now(), $syncEnabledAt);

            if ($period === null) {
                $stats['skipped']++;

                continue;
            }

            try {
                $this->syncReport(
                    (int) $integrationProject->project_id,
                    $settings,
                    $report,
                    Carbon::instance($period->getStart()),
                    Carbon::instance($period->getEnd()),
                );
                $stats['synced']++;
            } catch (Throwable $e) {
                $stats['failed']++;
                $stats['errors'][(int) $integrationProject->project_id] = $e->getMessage();
                Log::channel('yandex_metrika')->error('Metrika report sync failed', [
                    'project_id' => $integrationProject->project_id,
                    'report' => $report,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $settings
     *
     * @throws Throwable ошибка API или записи в БД
     */
    public function syncReport(int $projectId, array $settings, string $report, Carbon $from, Carbon $to): void
    {
        $counterTimezone = filled($settings['counter_time_zone'] ?? null)
            ? (string) $settings['counter_time_zone']
            : null;
        $timezone = $this->agencyRepository->getProjectTimeZone($projectId) ?? $counterTimezone;

        $filters = is_array($settings['filters'] ?? null) ? $settings['filters'] : null;
        $dataMode = (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE);
        $attributionModel = (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL);
        $goalIds = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []);
        $goalsMetric = YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($settings['goals_metric'] ?? null);
        $visitsMetric = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($settings['visits_metric'] ?? null);

        $this->metrikaService->setupClientFromSettings($settings);

        match ($report) {
            self::GOALS_SEARCH_ENGINES => $this->saveSearchEnginesGoals(
                $projectId,
                $goalIds,
                $from,
                $to,
                $this->metrikaService->fetchSearchEnginesGoalsStats(
                    $from, $to, $goalIds, $goalsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone
                ),
            ),
            self::GOALS_UTM => $this->saveUtmGoals($projectId, $settings, $from, $to, $goalIds, $goalsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone),
            self::GOALS_CONVERSIONS => $this->repository->upsertGoalConversions(
                $projectId,
                $this->mapGoalRows($this->metrikaService->fetchConversionsGoalsStats(
                    $from, $to, $goalIds, $goalsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone
                )),
            ),
            self::GOALS_DIRECT_SUMMARY => $this->repository->upsertGoalDirectSummary(
                $projectId,
                $this->mapGoalRows($this->metrikaService->fetchDirectSummaryGoalsStats(
                    $from, $to, $goalIds, $goalsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone
                )),
            ),
            self::VISITS_SEARCH_ENGINES => $this->saveSearchEnginesVisits($projectId, $settings, $from, $to, $visitsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone),
            self::VISITS_SEARCH_QUERIES => $this->saveSearchQueriesVisits(
                $projectId,
                $visitsMetric,
                $this->metrikaService->fetchSearchQueriesVisitsStats(
                    $from, $to, $visitsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone,
                    (string) ($settings['search_queries_minus'] ?? '')
                ),
            ),
            self::VISITS_GEO => $this->saveGeoVisits(
                $projectId,
                $visitsMetric,
                $this->metrikaService->fetchGeoVisitsStats(
                    $from, $to, $visitsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone
                ),
            ),
            default => throw new \InvalidArgumentException('Неизвестный отчёт Метрики: '.$report),
        };
    }

    /**
     * @param  list<int>  $goalIds
     * @param  list<array{search_engine: string, month: string, goal_id: int, value: int}>  $rows
     */
    private function saveSearchEnginesGoals(int $projectId, array $goalIds, Carbon $from, Carbon $to, array $rows): void
    {
        $engineTotals = [];
        $byGoal = [];

        foreach ($rows as $row) {
            $engineKey = $row['search_engine'].'|'.$row['month'];
            $engineTotals[$engineKey] = ($engineTotals[$engineKey] ?? [
                'search_engine' => $row['search_engine'],
                'month' => $row['month'],
                'value' => 0,
            ]);
            $engineTotals[$engineKey]['value'] += $row['value'];

            $goalKey = $row['search_engine'].'|'.$row['month'].'|'.$row['goal_id'];
            $byGoal[$goalKey] = ($byGoal[$goalKey] ?? 0) + $row['value'];
        }

        foreach ($engineTotals as $row) {
            $this->repository->upsertSearchEnginesConversions(
                $projectId,
                $row['search_engine'],
                $row['month'],
                $row['value']
            );
        }

        $names = $this->goalNames($goalIds);
        $engines = [YandexMetrikaSearchEngine::YANDEX->value, YandexMetrikaSearchEngine::GOOGLE->value];

        foreach ($this->monthsInRange($from, $to) as $month) {
            foreach ($engines as $engine) {
                foreach ($goalIds as $goalId) {
                    $this->repository->upsertSearchEngineGoal(
                        $projectId,
                        $engine,
                        $goalId,
                        $names[$goalId] ?? ('Цель '.$goalId),
                        $month,
                        $byGoal[$engine.'|'.$month.'|'.$goalId] ?? 0
                    );
                }
            }
        }
    }

    /**
     * @param  list<int>  $goalIds
     * @return array<int, string>
     */
    private function goalNames(array $goalIds): array
    {
        $names = [];
        foreach ($goalIds as $goalId) {
            $names[$goalId] = 'Цель '.$goalId;
        }

        try {
            foreach ($this->metrikaService->listGoalOptions() as $option) {
                $id = (int) ($option['id'] ?? 0);
                if (! isset($names[$id])) {
                    continue;
                }

                $name = trim((string) ($option['name'] ?? ''));
                if ($name !== '') {
                    $names[$id] = $name;
                }
            }
        } catch (Throwable) {
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function monthsInRange(Carbon $from, Carbon $to): array
    {
        $months = [];
        $current = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();

        while ($current->lte($end)) {
            $months[] = $current->toDateString();
            $current->addMonth();
        }

        return $months;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<int>  $goalIds
     * @param  array<string, mixed>|null  $filters
     */
    private function saveUtmGoals(
        int $projectId,
        array $settings,
        Carbon $from,
        Carbon $to,
        array $goalIds,
        string $goalsMetric,
        ?array $filters,
        string $dataMode,
        string $attributionModel,
        ?string $timezone,
        ?string $counterTimezone,
    ): void {
        $utmFilterMode = YandexMetrikaIntegrationSettingsData::normalizeUtmFilterMode($settings['utm_filter_mode'] ?? null);
        $utmValue = trim((string) ($settings['utm_'.$utmFilterMode] ?? ''));

        $rows = $this->metrikaService->fetchUtmGoalsStats(
            $from, $to, $goalIds, $goalsMetric, $utmFilterMode, $utmValue, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone
        );

        $dbRows = [];
        foreach ($rows as $row) {
            if (($row['value'] ?? 0) <= 0) {
                continue;
            }

            $dbRow = [
                'goal_name' => '',
                'achieved_date' => $row['date'],
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null,
                'utm_content' => null,
                'utm_term' => null,
            ];
            $dbRow[self::UTM_DIMENSION_TO_FIELD[$row['utm_dimension']] ?? 'utm_source'] = $row['utm_value'];

            for ($i = 0; $i < $row['value']; $i++) {
                $dbRows[] = $dbRow;
            }
        }

        $this->repository->replaceGoalUtmRows($projectId, $from->format('Y-m-d'), $to->format('Y-m-d'), $dbRows);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>|null  $filters
     */
    private function saveSearchEnginesVisits(
        int $projectId,
        array $settings,
        Carbon $from,
        Carbon $to,
        string $visitsMetric,
        ?array $filters,
        string $dataMode,
        string $attributionModel,
        ?string $timezone,
        ?string $counterTimezone,
    ): void {
        [$searchEnginesAll, $searchEngineIds] = YandexMetrikaIntegrationSettingsData::resolveSearchEnginesSelection(collect($settings));

        $rows = $this->metrikaService->fetchSearchEnginesVisitsStats(
            $from, $to, $visitsMetric, $filters, $dataMode, $attributionModel, $timezone, $counterTimezone, $searchEnginesAll, $searchEngineIds
        );

        foreach ($rows as $row) {
            $this->repository->upsertSearchEnginesVisits($projectId, $row['search_engine'], $row['month'], $row['value']);
        }
    }

    /**
     * @param  list<array{phrase: string, month: string, value: int}>  $rows
     */
    private function saveSearchQueriesVisits(int $projectId, string $visitsMetric, array $rows): void
    {
        foreach ($rows as $row) {
            $this->repository->upsertSearchQueriesVisits($projectId, $row['phrase'], $row['month'], $visitsMetric, $row['value']);
        }
    }

    /**
     * @param  list<array{city: string, month: string, value: int}>  $rows
     */
    private function saveGeoVisits(int $projectId, string $visitsMetric, array $rows): void
    {
        foreach ($rows as $row) {
            $this->repository->upsertVisitsGeo($projectId, $row['city'], $row['month'], $visitsMetric, $row['value']);
        }
    }

    /**
     * @param  list<array{goal_name: string, month: string, value: int}>  $rows
     * @return list<array{goal_name: string, month: string, conversions: int}>
     */
    private function mapGoalRows(array $rows): array
    {
        return array_map(fn (array $row) => [
            'goal_name' => $row['goal_name'],
            'month' => $row['month'],
            'conversions' => $row['value'],
        ], $rows);
    }
}
