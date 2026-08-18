<?php

namespace App\Services;

use App\Contracts\YandexMetrikaClientInterface;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Data\YandexMetrika\GoalDTO;
use App\Data\YandexMetrika\VisitReportDTO;
use App\Factories\YandexMetrikaClientFactory;
use App\Models\Agency;
use App\Support\YandexMetrikaTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Log;
use Src\Domain\YandexMetrika\YandexMetrikaFiltersBuilder;

class YandexMetrikaService
{
    private readonly YandexMetrikaClientInterface $client;

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
        if (!isset($this->client)) {
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
