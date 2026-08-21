<?php

namespace App\Services;

use App\Contracts\YandexMetrikaClientInterface;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Data\YandexMetrika\GoalDTO;
use App\Data\YandexMetrika\VisitReportDTO;
use App\Factories\YandexMetrikaClientFactory;
use App\Models\Agency;
use App\Support\YandexMetrikaSearchEngine;
use App\Support\YandexMetrikaTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Log;
use Src\Domain\YandexMetrika\GeographyDisplayList;
use Src\Domain\YandexMetrika\SearchEnginesDisplayList;
use Src\Domain\YandexMetrika\SearchQueriesMinusList;
use Src\Domain\YandexMetrika\YandexMetrikaFiltersBuilder;
use Src\Domain\YandexMetrika\YandexMetrikaUtmFilterBuilder;

class YandexMetrikaService
{
    private ?YandexMetrikaClientInterface $client = null;

    public function __construct(
        private readonly YandexMetrikaClientFactory $clientFactory,
        private readonly YandexMetrikaFiltersBuilder $filtersBuilder,
        private readonly YandexMetrikaUtmFilterBuilder $utmFilterBuilder = new YandexMetrikaUtmFilterBuilder()
    ) {}

    /**
     * Инициализация клиента с токеном и ID счетчика
     */
    public function setupClient(string $token, string $clientLogin, ?int $counterId = null): void
    {
        $this->client = $this->clientFactory->create($token, $clientLogin, $counterId);
    }

    private function getClient(): YandexMetrikaClientInterface
    {
        if ($this->client === null) {
            throw new \RuntimeException('YandexMetrikaClient not initialized. Call setupClient() first.');
        }
        return $this->client;
    }

    /**
     * Получить список целей счетчика
     *
     * @return Collection<GoalDTO>
     * @throws \Exception
     */
    public function getGoals(): Collection
    {
        try {
            $response = $this->getClient()->getGoals();

            return collect($response['goals'] ?? [])
                ->map(fn($goal) => new GoalDTO(
                    $goal['id'],
                    $goal['name'],
                    $goal['type'],
                    $goal['default_price'],
                    $goal['is_retargeting'],
                    $goal['goal_source'],
                    $goal['is_favorite'],
                    $goal['status'],
                    $goal['depth'],
                ));

        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e);
            throw new \Exception('Failed to get goals', 0, $e);
        }
    }

    /**
     * Получить отчет по визитам с группировкой
     *
     * @param array $dimensions Поля для группировки (например: ['ym:s:date', 'ym:s:regionCountry'])
     * @param array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null $filters
     * @return VisitReportDTO
     * @throws \Exception
     */
    public function getVisitsReport(
        Carbon $startDate,
        Carbon $endDate,
        array $dimensions = ['ym:s:date'],
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        ?string $timezone = null,
        ?string $counterTimezone = null
    ): VisitReportDTO
    {
        $this->validateDateRange($startDate, $endDate);

        try {
            $params = $this->applyReportFilters([
                'date1' => $startDate->format('Y-m-d'),
                'date2' => $endDate->format('Y-m-d'),
                'metrics' => 'ym:s:visits,ym:s:users',
                'dimensions' => implode(',', $dimensions),
            ], $filters, $dataMode);
            $params = $this->applyReportTimezone($params, $timezone, $startDate, $counterTimezone);

            $response = $this->getClient()->getVisitsReport($params);

            return new VisitReportDTO(
                startDate: Carbon::parse($response['query']['date1']),
                endDate: Carbon::parse($response['query']['date2']),
                visits: (int)$response['totals'][0],
                users: (int)$response['totals'][1],
                queryParams: $response['query'],
                totals: $response['totals'],
                minValues: $response['min'],
                maxValues: $response['max']
            );

        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]);
            throw new \Exception('Failed to get visits report', 0, $e);
        }
    }

    /**
     * Получить данные по достижениям цели
     *
     * @param array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null $filters
     * @throws \Exception
     */
    public function getGoalAchievements(
        int $goalId,
        array $params = [],
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        ?string $timezone = null,
        ?string $counterTimezone = null
    ): array {
        try {
            $params = $this->applyReportFilters($params, $filters, $dataMode);
            $at = isset($params['date1'])
                ? Carbon::parse((string) $params['date1'])
                : Carbon::now();

            return $this->getClient()->getGoalAchievements(
                $goalId,
                $this->applyReportTimezone($params, $timezone, $at, $counterTimezone)
            );
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, ['goal_id' => $goalId]);
            throw new \Exception('Failed to get goal achievements', 0, $e);
        }
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function listGoalOptions(): array
    {
        $response = $this->getClient()->getGoals();
        $options = [];

        foreach ($response['goals'] ?? [] as $goal) {
            $id = (int) ($goal['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $options[] = [
                'id' => $id,
                'name' => (string) ($goal['name'] ?? ('Цель '.$id)),
            ];
        }

        return $options;
    }

    /**
     * Список корневых ПС (ym:s:searchEngineRoot) за период.
     *
     * @return list<array{id: string, name: string}>
     */
    public function listSearchEngineRootOptions(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        $dateTo ??= Carbon::yesterday();
        $dateFrom ??= $dateTo->copy()->subDays(29);

        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        try {
            $params = $this->applyReportFilters([
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => 'ym:s:visits',
                'dimensions' => SearchEnginesDisplayList::SEARCH_ENGINE_ROOT_DIMENSION,
                'filters' => "ym:s:trafficSource=='organic'",
                'limit' => 10000,
            ], null, YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE);

            $response = $this->getClient()->getVisitsReport($params);
            $options = [];
            $seen = [];

            foreach ($response['data'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
                $dim = is_array($dimensions[0] ?? null) ? $dimensions[0] : [];
                $id = trim((string) ($dim['id'] ?? ''));
                $name = trim((string) ($dim['name'] ?? ''));

                if ($id === '' || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $options[] = [
                    'id' => $id,
                    'name' => $name !== '' ? $name : $id,
                ];
            }

            usort(
                $options,
                static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name'])
            );

            return $options;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
            ]);
            throw new \Exception('Failed to list search engine root options', 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function countSearchEnginesGoalsForDate(array $settings, Carbon $date): int
    {
        $this->setupClientFromSettings($settings);

        $rows = $this->fetchSearchEnginesGoalsStats(
            $date,
            $date,
            YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []),
            YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($settings['goals_metric'] ?? null),
            is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
            (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
            (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
            null,
            filled($settings['counter_time_zone'] ?? null) ? (string) $settings['counter_time_zone'] : null
        );

        return array_sum(array_column($rows, 'value'));
    }

    /**
     * @param  list<int|string>  $goalIds
     * @param  array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null  $filters
     * @return list<array{search_engine: string, month: string, value: int}>
     */
    public function fetchSearchEnginesGoalsStats(
        Carbon $dateFrom,
        Carbon $dateTo,
        array $goalIds,
        string $goalsMetric = YandexMetrikaIntegrationSettingsData::DEFAULT_GOALS_METRIC,
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        string $attributionModel = YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
        ?string $timezone = null,
        ?string $counterTimezone = null
    ): array {
        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $normalizedGoalIds = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($goalIds);
        if ($normalizedGoalIds === []) {
            throw new \InvalidArgumentException('Выберите хотя бы одну цель');
        }

        $metricName = YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($goalsMetric) === YandexMetrikaIntegrationSettingsData::GOALS_METRIC_GOAL_REACHES
            ? 'reaches'
            : 'visits';
        $metrics = implode(',', array_map(
            fn (int $id) => 'ym:s:goal'.$id.$metricName,
            $normalizedGoalIds
        ));

        $includeMonth = $dateFrom->format('Y-m') !== $dateTo->format('Y-m');
        // Как отчёт «Поисковые системы» / preset search_engines: корень ПС + organic.
        $engineDimension = SearchEnginesDisplayList::SEARCH_ENGINE_ROOT_DIMENSION;
        $dimensions = $includeMonth
            ? $engineDimension.',ym:s:month'
            : $engineDimension;

        try {
            $params = $this->applyReportFilters([
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => $metrics,
                'dimensions' => $dimensions,
                'filters' => "ym:s:<attribution>TrafficSource=='organic'",
                'limit' => 10000,
            ], $filters, $dataMode);
            $params = $this->applyReportAttribution($params, $attributionModel);
            $params = $this->applyReportTimezone($params, $timezone, $dateFrom, $counterTimezone);

            $response = $this->getClient()->getVisitsReport($params);

            return $this->aggregateSearchEnginesGoalRows($response, $dateFrom, $includeMonth);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
                'goals' => $normalizedGoalIds,
            ]);
            throw new \Exception('Failed to get search engines goals report', 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function countSearchEnginesVisitsForDate(array $settings, Carbon $date): int
    {
        $this->setupClientFromSettings($settings);

        [$searchEnginesAll, $searchEngineIds] = YandexMetrikaIntegrationSettingsData::resolveSearchEnginesSelection(
            collect($settings)
        );

        $rows = $this->fetchSearchEnginesVisitsStats(
            $date,
            $date,
            YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($settings['visits_metric'] ?? null),
            is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
            (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
            (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
            null,
            filled($settings['counter_time_zone'] ?? null) ? (string) $settings['counter_time_zone'] : null,
            $searchEnginesAll,
            $searchEngineIds
        );

        return array_sum(array_column($rows, 'value'));
    }

    /**
     * @param  array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null  $filters
     * @param  list<string>  $searchEngineIds
     * @return list<array{search_engine: string, search_engine_label: string, month: string, value: int}>
     */
    public function fetchSearchEnginesVisitsStats(
        Carbon $dateFrom,
        Carbon $dateTo,
        string $visitsMetric = YandexMetrikaIntegrationSettingsData::DEFAULT_VISITS_METRIC,
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        string $attributionModel = YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
        ?string $timezone = null,
        ?string $counterTimezone = null,
        bool $searchEnginesAll = true,
        array $searchEngineIds = []
    ): array {
        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $normalizedMetric = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($visitsMetric);
        $metrics = $normalizedMetric === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS
            ? 'ym:s:users'
            : 'ym:s:visits';

        $includeMonth = $dateFrom->format('Y-m') !== $dateTo->format('Y-m');
        $engineRootDimension = SearchEnginesDisplayList::SEARCH_ENGINE_ROOT_DIMENSION;
        $dimensions = $includeMonth
            ? $engineRootDimension.',ym:s:month'
            : $engineRootDimension;

        try {
            $params = [
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => $metrics,
                'dimensions' => $dimensions,
                'limit' => 10000,
            ];

            $searchEngineFilter = SearchEnginesDisplayList::buildSearchEngineRootFilter(
                $searchEnginesAll,
                YandexMetrikaIntegrationSettingsData::normalizeSearchEngineIds($searchEngineIds)
            );
            if ($searchEngineFilter !== null) {
                $params['filters'] = $searchEngineFilter;
            }

            $params = $this->applyReportFilters($params, $filters, $dataMode);
            $params = $this->applyReportAttribution($params, $attributionModel);
            $params = $this->applyReportTimezone($params, $timezone, $dateFrom, $counterTimezone);

            $response = $this->getClient()->getVisitsReport($params);

            return $this->aggregateSearchEnginesVisitsRows($response, $dateFrom, $includeMonth);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
                'visits_metric' => $normalizedMetric,
            ]);
            throw new \Exception('Failed to get search engines visits report', 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function countSearchQueriesVisitsForDate(array $settings, Carbon $date): int
    {
        $this->setupClientFromSettings($settings);

        $rows = $this->fetchSearchQueriesVisitsStats(
            $date,
            $date,
            YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($settings['visits_metric'] ?? null),
            is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
            (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
            (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
            null,
            filled($settings['counter_time_zone'] ?? null) ? (string) $settings['counter_time_zone'] : null,
            (string) ($settings['search_queries_minus'] ?? '')
        );

        return array_sum(array_column($rows, 'value'));
    }

    /**
     * @param  array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null  $filters
     * @return list<array{phrase: string, month: string, visits: int, visitors: int, value: int}>
     */
    public function fetchSearchQueriesVisitsStats(
        Carbon $dateFrom,
        Carbon $dateTo,
        string $visitsMetric = YandexMetrikaIntegrationSettingsData::DEFAULT_VISITS_METRIC,
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        string $attributionModel = YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
        ?string $timezone = null,
        ?string $counterTimezone = null,
        string $searchQueriesMinus = ''
    ): array {
        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $normalizedMetric = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($visitsMetric);
        $metrics = $normalizedMetric === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS
            ? 'ym:s:users'
            : 'ym:s:visits';

        $includeMonth = $dateFrom->format('Y-m') !== $dateTo->format('Y-m');
        $phraseDimension = SearchQueriesMinusList::SEARCH_PHRASE_DIMENSION;
        $dimensions = $includeMonth
            ? $phraseDimension.',ym:s:month'
            : $phraseDimension;

        try {
            $params = [
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => $metrics,
                'dimensions' => $dimensions,
                'limit' => 10000,
            ];

            $minusFilter = SearchQueriesMinusList::buildFilter($searchQueriesMinus);
            if ($minusFilter !== null) {
                $params['filters'] = $minusFilter;
            }

            $params = $this->applyReportFilters($params, $filters, $dataMode);
            $params = $this->applyReportAttribution($params, $attributionModel);
            $params = $this->applyReportTimezone($params, $timezone, $dateFrom, $counterTimezone);

            $response = $this->getClient()->getVisitsReport($params);

            return $this->aggregateSearchQueriesVisitsRows(
                $response,
                $dateFrom,
                $includeMonth,
                $normalizedMetric
            );
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
                'visits_metric' => $normalizedMetric,
            ]);
            throw new \Exception('Failed to get search queries visits report', 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function countGeoVisitsForDate(array $settings, Carbon $date): int
    {
        $this->setupClientFromSettings($settings);

        $rows = $this->fetchGeoVisitsStats(
            $date,
            $date,
            YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($settings['visits_metric'] ?? null),
            is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
            (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
            (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
            null,
            filled($settings['counter_time_zone'] ?? null) ? (string) $settings['counter_time_zone'] : null
        );

        return array_sum(array_column($rows, 'value'));
    }

    /**
     * Переходы из отчёта «География» (preset geo_country, группировка по городу).
     *
     * @param  array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null  $filters
     * @return list<array{city: string, month: string, visits: int, visitors: int, value: int}>
     */
    public function fetchGeoVisitsStats(
        Carbon $dateFrom,
        Carbon $dateTo,
        string $visitsMetric = YandexMetrikaIntegrationSettingsData::DEFAULT_VISITS_METRIC,
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        string $attributionModel = YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
        ?string $timezone = null,
        ?string $counterTimezone = null
    ): array {
        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $normalizedMetric = YandexMetrikaIntegrationSettingsData::normalizeVisitsMetric($visitsMetric);
        $metrics = $normalizedMetric === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS
            ? 'ym:s:users'
            : 'ym:s:visits';

        $includeMonth = $dateFrom->format('Y-m') !== $dateTo->format('Y-m');
        $cityDimension = GeographyDisplayList::CITY_DIMENSION;
        $dimensions = $includeMonth
            ? $cityDimension.',ym:s:month'
            : $cityDimension;

        try {
            $params = $this->applyReportFilters([
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => $metrics,
                'dimensions' => $dimensions,
                'limit' => 10000,
            ], $filters, $dataMode);
            $params = $this->applyReportAttribution($params, $attributionModel);
            $params = $this->applyReportTimezone($params, $timezone, $dateFrom, $counterTimezone);

            $response = $this->getClient()->getVisitsReport($params);

            return $this->aggregateGeoVisitsRows(
                $response,
                $dateFrom,
                $includeMonth,
                $normalizedMetric
            );
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
                'visits_metric' => $normalizedMetric,
            ]);
            throw new \Exception('Failed to get geography visits report', 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function countUtmGoalsForDate(array $settings, Carbon $date): int
    {
        $this->setupClientFromSettings($settings);

        $rows = $this->fetchUtmGoalsStats(
            $date,
            $date,
            YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []),
            YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($settings['goals_metric'] ?? null),
            YandexMetrikaIntegrationSettingsData::normalizeUtmFilterMode($settings['utm_filter_mode'] ?? null),
            $this->activeUtmValue($settings),
            is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
            (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
            (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
            null,
            filled($settings['counter_time_zone'] ?? null) ? (string) $settings['counter_time_zone'] : null
        );

        return array_sum(array_column($rows, 'value'));
    }

    /**
     * @param  list<int|string>  $goalIds
     * @param  array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null  $filters
     * @return list<array{utm_dimension: string, utm_value: string, date: string, value: int}>
     */
    public function fetchUtmGoalsStats(
        Carbon $dateFrom,
        Carbon $dateTo,
        array $goalIds,
        string $goalsMetric = YandexMetrikaIntegrationSettingsData::DEFAULT_GOALS_METRIC,
        string $utmFilterMode = YandexMetrikaIntegrationSettingsData::DEFAULT_UTM_FILTER_MODE,
        string $utmValue = '',
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        string $attributionModel = YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
        ?string $timezone = null,
        ?string $counterTimezone = null
    ): array {
        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $normalizedGoalIds = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($goalIds);
        if ($normalizedGoalIds === []) {
            throw new \InvalidArgumentException('Выберите хотя бы одну цель');
        }

        $utmDimension = $this->utmFilterBuilder->dimension($utmFilterMode)
            ?? 'ym:s:<attribution>UTMSource';

        $metricName = YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($goalsMetric) === YandexMetrikaIntegrationSettingsData::GOALS_METRIC_GOAL_REACHES
            ? 'reaches'
            : 'visits';
        $metrics = implode(',', array_map(
            fn (int $id) => 'ym:s:goal' . $id . $metricName,
            $normalizedGoalIds
        ));

        $dimensions = $utmDimension . ',ym:s:date';

        try {
            $params = $this->applyReportFilters([
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => $metrics,
                'dimensions' => $dimensions,
                'limit' => 10000,
            ], $filters, $dataMode);

            $utmFilter = $this->utmFilterBuilder->build($utmFilterMode, $utmValue);
            if ($utmFilter !== null) {
                $existing = trim((string) ($params['filters'] ?? ''));
                $params['filters'] = $existing === ''
                    ? $utmFilter
                    : '(' . $existing . ') AND (' . $utmFilter . ')';
            }

            $params = $this->applyReportAttribution($params, $attributionModel);
            $params = $this->applyReportTimezone($params, $timezone, $dateFrom, $counterTimezone);

            $response = $this->getClient()->getVisitsReport($params);

            return $this->aggregateUtmGoalRows($response, $utmDimension);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
                'goals' => $normalizedGoalIds,
                'utm_mode' => $utmFilterMode,
            ]);
            throw new \Exception('Failed to get UTM goals report', 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<array{utm_dimension: string, utm_value: string, date: string, value: int}>
     */
    private function aggregateUtmGoalRows(array $response, string $utmDimension): array
    {
        $aggregated = [];

        foreach ($response['data'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
            $utmVal = is_array($dimensions[0] ?? null)
                ? (string) ($dimensions[0]['name'] ?? '')
                : '';
            $dateVal = is_array($dimensions[1] ?? null)
                ? (string) ($dimensions[1]['name'] ?? '')
                : '';
            $metrics = is_array($row['metrics'] ?? null) ? $row['metrics'] : [];
            $value = (int) round(array_sum(array_map('floatval', $metrics)));

            $key = $utmVal . '|' . $dateVal;
            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'utm_dimension' => $utmDimension,
                    'utm_value' => $utmVal,
                    'date' => $dateVal,
                    'value' => 0,
                ];
            }

            $aggregated[$key]['value'] += $value;
        }

        return array_values($aggregated);
    }

    private function activeUtmValue(array $settings): string
    {
        $mode = YandexMetrikaIntegrationSettingsData::normalizeUtmFilterMode($settings['utm_filter_mode'] ?? null);

        return trim((string) ($settings['utm_' . $mode] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function setupClientFromSettings(array $settings): void
    {
        $token = trim((string) ($settings['oauth_token'] ?? ''));
        $login = trim((string) ($settings['oauth_yandex_login'] ?? ''));
        $counterId = (int) ($settings['counter_id'] ?? 0);

        $this->setupClient($token, $login, $counterId > 0 ? $counterId : null);
    }

    /**
     * @param array<string, mixed> $params
     * @param array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null $filters
     * @return array<string, mixed>
     */
    private function applyReportFilters(array $params, ?array $filters, string $dataMode): array
    {
        $built = $this->filtersBuilder->build($filters, $dataMode);
        if ($built === null) {
            return $params;
        }

        $existing = trim((string) ($params['filters'] ?? ''));
        $params['filters'] = $existing === ''
            ? $built
            : '(' . $existing . ') AND (' . $built . ')';

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function applyReportTimezone(
        array $params,
        ?string $timezone,
        Carbon $at,
        ?string $counterTimezone = null
    ): array {
        $offset = YandexMetrikaTimezone::offsetIfDiffers(
            $timezone ?? $this->resolveTimezone(),
            $counterTimezone,
            $at
        );

        if ($offset !== null) {
            $params['timezone'] = $offset;
        }

        return $params;
    }

    private function resolveTimezone(?string $timezone = null): string
    {
        if ($timezone !== null && $timezone !== '') {
            return $timezone;
        }

        $agencyId = session('current_agency_id') ?? Auth::user()?->agencies()->first()?->id;

        if ($agencyId) {
            $agencyTimezone = Agency::query()->whereKey($agencyId)->value('time_zone');

            if ($agencyTimezone) {
                return $agencyTimezone;
            }
        }

        return (string) config('app.timezone', 'UTC');
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function applyReportAttribution(array $params, string $attributionModel): array
    {
        $attribution = trim($attributionModel);
        if ($attribution !== '') {
            $params['attribution'] = $attribution;
        }

        return $params;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<array{search_engine: string, month: string, value: int}>
     */
    private function aggregateSearchEnginesGoalRows(array $response, Carbon $fallbackDate, bool $includeMonth): array
    {
        $aggregated = [];

        foreach ($response['data'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
            $engineName = is_array($dimensions[0] ?? null)
                ? (string) ($dimensions[0]['name'] ?? '')
                : '';
            $monthName = $includeMonth && is_array($dimensions[1] ?? null)
                ? (string) ($dimensions[1]['name'] ?? '')
                : '';
            $metrics = is_array($row['metrics'] ?? null) ? $row['metrics'] : [];
            $value = (int) round(array_sum(array_map('floatval', $metrics)));

            $engine = YandexMetrikaSearchEngine::normalize($engineName);
            $month = $this->parseMonthDimension($monthName, $fallbackDate);
            $key = $engine.'|'.$month;

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'search_engine' => $engine,
                    'month' => $month,
                    'value' => 0,
                ];
            }

            $aggregated[$key]['value'] += $value;
        }

        return array_values($aggregated);
    }

    /**
     * Агрегация переходов: ключ — root-ID из API, label — локализованное name.
     *
     * @param  array<string, mixed>  $response
     * @return list<array{search_engine: string, search_engine_label: string, month: string, value: int}>
     */
    private function aggregateSearchEnginesVisitsRows(array $response, Carbon $fallbackDate, bool $includeMonth): array
    {
        $aggregated = [];

        foreach ($response['data'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
            $engineDim = is_array($dimensions[0] ?? null) ? $dimensions[0] : [];
            $engineId = trim((string) ($engineDim['id'] ?? ''));
            $engineLabel = trim((string) ($engineDim['name'] ?? ''));
            if ($engineId === '') {
                $engineId = $engineLabel;
            }
            if ($engineLabel === '') {
                $engineLabel = $engineId;
            }

            $monthName = $includeMonth && is_array($dimensions[1] ?? null)
                ? (string) ($dimensions[1]['name'] ?? '')
                : '';
            $metrics = is_array($row['metrics'] ?? null) ? $row['metrics'] : [];
            $value = (int) round(array_sum(array_map('floatval', $metrics)));

            if ($engineId === '') {
                continue;
            }

            $month = $this->parseMonthDimension($monthName, $fallbackDate);
            $key = $engineId.'|'.$month;

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'search_engine' => $engineId,
                    'search_engine_label' => $engineLabel,
                    'month' => $month,
                    'value' => 0,
                ];
            }

            $aggregated[$key]['value'] += $value;
        }

        return array_values($aggregated);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<array{phrase: string, month: string, visits: int, visitors: int, value: int}>
     */
    private function aggregateSearchQueriesVisitsRows(
        array $response,
        Carbon $fallbackDate,
        bool $includeMonth,
        string $visitsMetric
    ): array {
        $aggregated = [];
        $isUsers = $visitsMetric === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS;

        foreach ($response['data'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
            $phraseDim = is_array($dimensions[0] ?? null) ? $dimensions[0] : [];
            $phrase = trim((string) ($phraseDim['name'] ?? ''));
            if ($phrase === '') {
                $phrase = trim((string) ($phraseDim['id'] ?? ''));
            }

            $monthName = $includeMonth && is_array($dimensions[1] ?? null)
                ? (string) ($dimensions[1]['name'] ?? '')
                : '';
            $metrics = is_array($row['metrics'] ?? null) ? $row['metrics'] : [];
            $value = (int) round(array_sum(array_map('floatval', $metrics)));

            if ($phrase === '') {
                continue;
            }

            $month = $this->parseMonthDimension($monthName, $fallbackDate);
            $key = $phrase.'|'.$month;

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'phrase' => $phrase,
                    'month' => $month,
                    'visits' => 0,
                    'visitors' => 0,
                    'value' => 0,
                ];
            }

            if ($isUsers) {
                $aggregated[$key]['visitors'] += $value;
            } else {
                $aggregated[$key]['visits'] += $value;
            }
            $aggregated[$key]['value'] += $value;
        }

        return array_values($aggregated);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<array{city: string, month: string, visits: int, visitors: int, value: int}>
     */
    private function aggregateGeoVisitsRows(
        array $response,
        Carbon $fallbackDate,
        bool $includeMonth,
        string $visitsMetric
    ): array {
        $aggregated = [];
        $isUsers = $visitsMetric === YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS;

        foreach ($response['data'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
            $cityDim = is_array($dimensions[0] ?? null) ? $dimensions[0] : [];
            $city = trim((string) ($cityDim['name'] ?? ''));
            if ($city === '') {
                $city = trim((string) ($cityDim['id'] ?? ''));
            }

            $monthName = $includeMonth && is_array($dimensions[1] ?? null)
                ? (string) ($dimensions[1]['name'] ?? '')
                : '';
            $metrics = is_array($row['metrics'] ?? null) ? $row['metrics'] : [];
            $value = (int) round(array_sum(array_map('floatval', $metrics)));

            if ($city === '') {
                continue;
            }

            $month = $this->parseMonthDimension($monthName, $fallbackDate);
            $key = $city.'|'.$month;

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'city' => $city,
                    'month' => $month,
                    'visits' => 0,
                    'visitors' => 0,
                    'value' => 0,
                ];
            }

            if ($isUsers) {
                $aggregated[$key]['visitors'] += $value;
            } else {
                $aggregated[$key]['visits'] += $value;
            }
            $aggregated[$key]['value'] += $value;
        }

        return array_values($aggregated);
    }

    private function parseMonthDimension(string $value, Carbon $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback->copy()->startOfMonth()->format('Y-m-d');
        }

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
                return $value.'-01';
            }

            return Carbon::parse($value)->startOfMonth()->format('Y-m-d');
        } catch (\Throwable) {
            return $fallback->copy()->startOfMonth()->format('Y-m-d');
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function countConversionsGoalsForDate(array $settings, Carbon $date): int
    {
        $this->setupClientFromSettings($settings);

        $rows = $this->fetchConversionsGoalsStats(
            $date,
            $date,
            YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []),
            YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($settings['goals_metric'] ?? null),
            is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
            (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
            (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
            null,
            filled($settings['counter_time_zone'] ?? null) ? (string) $settings['counter_time_zone'] : null
        );

        return array_sum(array_column($rows, 'value'));
    }

    /**
     * @param  list<int|string>  $goalIds
     * @param  array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null  $filters
     * @return list<array{goal_name: string, month: string, value: int}>
     */
    public function fetchConversionsGoalsStats(
        Carbon $dateFrom,
        Carbon $dateTo,
        array $goalIds,
        string $goalsMetric = YandexMetrikaIntegrationSettingsData::DEFAULT_GOALS_METRIC,
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        string $attributionModel = YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
        ?string $timezone = null,
        ?string $counterTimezone = null
    ): array {
        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $normalizedGoalIds = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($goalIds);
        if ($normalizedGoalIds === []) {
            throw new \InvalidArgumentException('Выберите хотя бы одну цель');
        }

        $metricName = YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($goalsMetric) === YandexMetrikaIntegrationSettingsData::GOALS_METRIC_GOAL_REACHES
            ? 'reaches'
            : 'visits';
        $metrics = implode(',', array_map(
            fn (int $id) => 'ym:s:goal'.$id.$metricName,
            $normalizedGoalIds
        ));

        $includeMonth = $dateFrom->format('Y-m') !== $dateTo->format('Y-m');
        $dimensions = $includeMonth
            ? 'ym:s:goal,ym:s:month'
            : 'ym:s:goal';

        try {
            $params = $this->applyReportFilters([
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => $metrics,
                'dimensions' => $dimensions,
                'limit' => 10000,
            ], $filters, $dataMode);
            $params = $this->applyReportAttribution($params, $attributionModel);
            $params = $this->applyReportTimezone($params, $timezone, $dateFrom, $counterTimezone);

            $response = $this->getClient()->getVisitsReport($params);
            $response['data'] = $this->filterGoalDimensionRowsByIds(
                is_array($response['data'] ?? null) ? $response['data'] : [],
                $normalizedGoalIds
            );

            return $this->aggregateConversionsGoalRows($response, $dateFrom, $includeMonth);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
                'goals' => $normalizedGoalIds,
            ]);
            throw new \Exception('Failed to get conversions goals report', 0, $e);
        }
    }

    /**
     * @return list<array{goal_name: string, month: string, value: int}>
     */
    private function aggregateConversionsGoalRows(array $response, Carbon $fallbackDate, bool $includeMonth): array
    {
        $aggregated = [];

        foreach ($response['data'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
            $goalName = is_array($dimensions[0] ?? null)
                ? (string) ($dimensions[0]['name'] ?? '')
                : '';
            $monthName = $includeMonth && is_array($dimensions[1] ?? null)
                ? (string) ($dimensions[1]['name'] ?? '')
                : '';
            $metrics = is_array($row['metrics'] ?? null) ? $row['metrics'] : [];
            $value = (int) round(array_sum(array_map('floatval', $metrics)));

            $month = $this->parseMonthDimension($monthName, $fallbackDate);
            $key = $goalName.'|'.$month;

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'goal_name' => $goalName,
                    'month' => $month,
                    'value' => 0,
                ];
            }

            $aggregated[$key]['value'] += $value;
        }

        return array_values($aggregated);
    }

    public function countDirectSummaryGoalsForDate(array $settings, Carbon $date): int
    {
        $this->setupClientFromSettings($settings);

        $rows = $this->fetchDirectSummaryGoalsStats(
            $date,
            $date,
            YandexMetrikaIntegrationSettingsData::normalizeGoalIds($settings['goals'] ?? []),
            YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($settings['goals_metric'] ?? null),
            is_array($settings['filters'] ?? null) ? $settings['filters'] : null,
            (string) ($settings['data_mode'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
            (string) ($settings['attribution_model'] ?? YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
            null,
            filled($settings['counter_time_zone'] ?? null) ? (string) $settings['counter_time_zone'] : null
        );

        return array_sum(array_column($rows, 'value'));
    }

    /**
     * @param  list<int|string>  $goalIds
     * @param  array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null  $filters
     * @return list<array{goal_name: string, month: string, value: int}>
     */
    public function fetchDirectSummaryGoalsStats(
        Carbon $dateFrom,
        Carbon $dateTo,
        array $goalIds,
        string $goalsMetric = YandexMetrikaIntegrationSettingsData::DEFAULT_GOALS_METRIC,
        ?array $filters = null,
        string $dataMode = YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
        string $attributionModel = YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
        ?string $timezone = null,
        ?string $counterTimezone = null
    ): array {
        if ($dateFrom->isAfter($dateTo)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        $normalizedGoalIds = YandexMetrikaIntegrationSettingsData::normalizeGoalIds($goalIds);
        if ($normalizedGoalIds === []) {
            throw new \InvalidArgumentException('Выберите хотя бы одну цель');
        }

        $metricName = YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($goalsMetric) === YandexMetrikaIntegrationSettingsData::GOALS_METRIC_GOAL_REACHES
            ? 'reaches'
            : 'visits';
        $metrics = implode(',', array_map(
            fn (int $id) => 'ym:s:goal'.$id.$metricName,
            $normalizedGoalIds
        ));

        $includeMonth = $dateFrom->format('Y-m') !== $dateTo->format('Y-m');
        $dimensions = $includeMonth
            ? 'ym:s:goal,ym:s:month'
            : 'ym:s:goal';

        try {
            $params = $this->applyReportFilters([
                'date1' => $dateFrom->format('Y-m-d'),
                'date2' => $dateTo->format('Y-m-d'),
                'metrics' => $metrics,
                'dimensions' => $dimensions,
                'limit' => 10000,
            ], $filters, $dataMode);
            $params = $this->applyReportAttribution($params, $attributionModel);
            $params = $this->applyReportTimezone($params, $timezone, $dateFrom, $counterTimezone);

            $existingFilters = $params['filters'] ?? '';
            // Отчёт «Директ, сводка» (preset sources_direct_summary): визиты с учтённым
            // кликом Директа (yclid) — непустой DirectClickOrder под выбранную атрибуцию.
            // Не путать с AdvEngine / «Рекламные системы».
            $directFilter = "ym:s:<attribution>DirectClickOrder!n";
            $params['filters'] = $existingFilters !== ''
                ? $existingFilters.' AND '.$directFilter
                : $directFilter;

            $response = $this->getClient()->getVisitsReport($params);

            // В разрезе ym:s:goal API может вернуть чужие цели с ненулевыми значениями
            // выбранной метрики — оставляем только запрошенные goal id.
            $response['data'] = $this->filterGoalDimensionRowsByIds(
                is_array($response['data'] ?? null) ? $response['data'] : [],
                $normalizedGoalIds
            );

            return $this->aggregateConversionsGoalRows($response, $dateFrom, $includeMonth);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $dateFrom->toDateString(),
                'end' => $dateTo->toDateString(),
                'goals' => $normalizedGoalIds,
            ]);
            throw new \Exception('Failed to get direct summary goals report', 0, $e);
        }
    }

    /**
     * @param  list<mixed>  $rows
     * @param  list<int>  $goalIds
     * @return list<mixed>
     */
    private function filterGoalDimensionRowsByIds(array $rows, array $goalIds): array
    {
        $allowedGoalIds = array_fill_keys(array_map('strval', $goalIds), true);

        return array_values(array_filter(
            $rows,
            static function (mixed $row) use ($allowedGoalIds): bool {
                if (! is_array($row)) {
                    return false;
                }
                $dimensions = is_array($row['dimensions'] ?? null) ? $row['dimensions'] : [];
                $id = trim((string) (is_array($dimensions[0] ?? null) ? ($dimensions[0]['id'] ?? '') : ''));

                return $id !== '' && isset($allowedGoalIds[$id]);
            }
        ));
    }

    /**
     * Валидация временного диапазона
     */
    private function validateDateRange(Carbon $start, Carbon $end): void
    {
        if ($start->isAfter($end)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        if ($end->diffInDays($start) > 365) {
            throw new \InvalidArgumentException('Maximum date range exceeded (365 days)');
        }
    }

    /**
     * Логирование ошибок
     */
    private function logError(string $method, \Throwable $e, array $context = []): void
    {
        Log::channel('yandex_metrika')->error("[$method] {$e->getMessage()}", [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'context' => $context
        ]);
    }
}
