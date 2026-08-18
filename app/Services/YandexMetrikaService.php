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
use Src\Domain\YandexMetrika\YandexMetrikaFiltersBuilder;

class YandexMetrikaService
{
    private ?YandexMetrikaClientInterface $client = null;

    public function __construct(
        private readonly YandexMetrikaClientFactory $clientFactory,
        private readonly YandexMetrikaFiltersBuilder $filtersBuilder
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
        $dimensions = $includeMonth
            ? 'ym:s:searchEngine,ym:s:month'
            : 'ym:s:searchEngine';

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
