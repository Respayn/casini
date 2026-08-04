<?php

namespace App\Services\Channels;

use App\Models\YandexDirectDailySpending;
use App\Repositories\IntegrationRepository;
use App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector;
use App\Services\YandexDirectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChannelDirectMetricsService
{
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    private const BULK_MAX_PROJECTS = 50;

    public function __construct(
        private readonly IntegrationRepository $integrationRepository,
        private readonly YandexDirectDailySpendCollector $dailySpendCollector,
        private readonly ChannelDirectApiThrottle $apiThrottle,
    ) {}

    public function hasDirectCredentials(Collection|array $integrations): bool
    {
        return $this->extractCredentials($integrations) !== null;
    }

    public function getCachedBudget(int $projectId): ?float
    {
        return $this->getCachedBudgetPayload($projectId)['value'] ?? null;
    }

    /**
     * @return array{value: ?float, updatedAt: ?Carbon}
     */
    public function getCachedBudgetPayload(int $projectId): array
    {
        $cached = Cache::get($this->budgetCacheKey($projectId));

        if (is_numeric($cached)) {
            return [
                'value' => (float) $cached,
                'updatedAt' => null,
            ];
        }

        if (! is_array($cached) || ! is_numeric($cached['value'] ?? null)) {
            return [
                'value' => null,
                'updatedAt' => null,
            ];
        }

        $updatedAt = null;
        if (filled($cached['updated_at'] ?? null)) {
            try {
                $updatedAt = Carbon::parse((string) $cached['updated_at']);
            } catch (\Throwable) {
                $updatedAt = null;
            }
        }

        return [
            'value' => (float) $cached['value'],
            'updatedAt' => $updatedAt,
        ];
    }

    /**
     * Сумма дневных расходов из БД за месяц (источник правды после ночного съёма).
     */
    public function getStoredSpendings(int $projectId, Carbon $month, bool $includeVat): ?float
    {
        [$from, $to] = $this->resolveMonthPeriod($month);

        $column = $includeVat ? 'cost_with_vat' : 'cost_without_vat';

        $query = YandexDirectDailySpending::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        if (! $query->exists()) {
            return null;
        }

        return round((float) $query->sum($column), 2);
    }

    /**
     * @return array{ok: bool, value: ?float, error: ?string, fromCache?: bool}
     */
    public function refreshBudget(int $projectId, bool $force = false): array
    {
        $credentials = $this->resolveCredentialsForProject($projectId);

        if ($credentials === null) {
            return [
                'ok' => false,
                'value' => null,
                'error' => 'Нет настроенной интеграции Яндекс.Директ',
            ];
        }

        $cached = $this->getCachedBudget($projectId);
        if (! $force && $cached !== null) {
            return [
                'ok' => true,
                'value' => $cached,
                'error' => null,
                'fromCache' => true,
            ];
        }

        $throttle = $this->apiThrottle->consume();
        if (! $throttle['ok']) {
            return [
                'ok' => false,
                'value' => $cached,
                'error' => $throttle['error'],
            ];
        }

        try {
            $service = $this->makeDirectService($credentials['token'], $credentials['client_login']);
            $value = round($service->getAccountBalance(), 2);
            $this->putBudgetCache($projectId, $value);

            return ['ok' => true, 'value' => $value, 'error' => null, 'fromCache' => false];
        } catch (\Throwable $e) {
            Log::warning('Channels: failed to refresh Direct budget', [
                'project_id' => $projectId,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'value' => $this->getCachedBudget($projectId),
                'error' => 'Не удалось получить остаток бюджета в Директе',
            ];
        }
    }

    /**
     * Расход за месяц: читаем из БД; в API идём только если данных нет или $force.
     * Один период = 2 запроса к Директу (с НДС / без), не по дню.
     *
     * @return array{ok: bool, value: ?float, error: ?string, fromCache?: bool}
     */
    public function refreshSpendings(
        int $projectId,
        Carbon $month,
        bool $includeVat,
        bool $force = false
    ): array {
        if ($this->resolveCredentialsForProject($projectId) === null) {
            return [
                'ok' => false,
                'value' => null,
                'error' => 'Нет настроенной интеграции Яндекс.Директ',
            ];
        }

        [$from, $to] = $this->resolveMonthPeriod($month);
        $expectedDays = $from->diffInDays($to) + 1;
        $storedDays = $this->countStoredDays($projectId, $from, $to);
        $value = $this->getStoredSpendings($projectId, $month, $includeVat);

        if (! $force && $storedDays >= $expectedDays) {
            return [
                'ok' => true,
                'value' => $value,
                'error' => null,
                'fromCache' => true,
            ];
        }

        $throttle = $this->apiThrottle->consume();
        if (! $throttle['ok']) {
            return [
                'ok' => false,
                'value' => $value,
                'error' => $throttle['error'],
            ];
        }

        $result = $this->dailySpendCollector->collectRange($projectId, $from, $to);
        $value = $this->getStoredSpendings($projectId, $month, $includeVat);

        if (! $result->ok) {
            return [
                'ok' => false,
                'value' => $value,
                'error' => $result->error ?? 'Не удалось получить расход в Директе',
            ];
        }

        return [
            'ok' => true,
            'value' => $value,
            'error' => null,
            'fromCache' => false,
        ];
    }

    public function countStoredDays(int $projectId, Carbon $from, Carbon $to): int
    {
        return (int) YandexDirectDailySpending::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->count();
    }

    /**
     * @param  array<int, int|string>  $projectIds
     * @return array{updated: int, failed: int, skipped: int}
     */
    public function refreshBudgets(array $projectIds): array
    {
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0];

        $throttle = $this->apiThrottle->consume();
        if (! $throttle['ok']) {
            return [
                'updated' => 0,
                'failed' => count($this->limitProjectIds($projectIds)),
                'skipped' => 0,
                'error' => $throttle['error'],
            ];
        }

        foreach ($this->limitProjectIds($projectIds) as $projectId) {
            // force + already consumed throttle for the bulk action
            $result = $this->refreshBudgetForcedWithoutThrottle((int) $projectId);

            if ($result['ok']) {
                $stats['updated']++;
            } elseif ($result['error'] === 'Нет настроенной интеграции Яндекс.Директ') {
                $stats['skipped']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Принудительный съём бюджета без повторного consume throttle (для bulk после одного consume).
     *
     * @return array{ok: bool, value: ?float, error: ?string}
     */
    private function refreshBudgetForcedWithoutThrottle(int $projectId): array
    {
        $credentials = $this->resolveCredentialsForProject($projectId);

        if ($credentials === null) {
            return [
                'ok' => false,
                'value' => null,
                'error' => 'Нет настроенной интеграции Яндекс.Директ',
            ];
        }

        try {
            $service = $this->makeDirectService($credentials['token'], $credentials['client_login']);
            $value = round($service->getAccountBalance(), 2);
            $this->putBudgetCache($projectId, $value);

            return ['ok' => true, 'value' => $value, 'error' => null];
        } catch (\Throwable $e) {
            Log::warning('Channels: failed to refresh Direct budget (bulk)', [
                'project_id' => $projectId,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'value' => $this->getCachedBudget($projectId),
                'error' => 'Не удалось получить остаток бюджета в Директе',
            ];
        }
    }

    /**
     * @param  array<int, int|string>  $projectIds
     * @return array{updated: int, failed: int, skipped: int, error?: string}
     */
    public function refreshSpendingsForProjects(array $projectIds, Carbon $month, bool $includeVat): array
    {
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0];

        $throttle = $this->apiThrottle->consume();
        if (! $throttle['ok']) {
            return [
                'updated' => 0,
                'failed' => count($this->limitProjectIds($projectIds)),
                'skipped' => 0,
                'error' => $throttle['error'],
            ];
        }

        foreach ($this->limitProjectIds($projectIds) as $projectId) {
            $result = $this->refreshSpendingsForcedWithoutThrottle((int) $projectId, $month, $includeVat);

            if ($result['ok']) {
                $stats['updated']++;
            } elseif (($result['error'] ?? null) === 'Нет настроенной интеграции Яндекс.Директ') {
                $stats['skipped']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @return array{ok: bool, value: ?float, error: ?string}
     */
    private function refreshSpendingsForcedWithoutThrottle(
        int $projectId,
        Carbon $month,
        bool $includeVat
    ): array {
        if ($this->resolveCredentialsForProject($projectId) === null) {
            return [
                'ok' => false,
                'value' => null,
                'error' => 'Нет настроенной интеграции Яндекс.Директ',
            ];
        }

        [$from, $to] = $this->resolveMonthPeriod($month);
        $result = $this->dailySpendCollector->collectRange($projectId, $from, $to);
        $value = $this->getStoredSpendings($projectId, $month, $includeVat);

        if (! $result->ok) {
            return [
                'ok' => false,
                'value' => $value,
                'error' => $result->error ?? 'Не удалось получить расход в Директе',
            ];
        }

        return ['ok' => true, 'value' => $value, 'error' => null];
    }

    /**
     * @return array{value: ?float, updatedAt: ?Carbon, projectId: int, canRefresh: bool}
     */
    public function budgetCellParams(int $projectId, Collection|array $integrations): array
    {
        $payload = $this->getCachedBudgetPayload($projectId);

        return [
            'value' => $payload['value'],
            'updatedAt' => $payload['updatedAt'],
            'projectId' => $projectId,
            'canRefresh' => $this->hasDirectCredentials($integrations),
        ];
    }

    /**
     * @return array{value: ?float, projectId: int, canRefresh: bool}
     */
    public function spendingsCellParams(
        int $projectId,
        Collection|array $integrations,
        Carbon $month,
        bool $includeVat
    ): array {
        return [
            'value' => $this->getStoredSpendings($projectId, $month, $includeVat),
            'projectId' => $projectId,
            'canRefresh' => $this->hasDirectCredentials($integrations),
        ];
    }

    /**
     * @return array{token: string, client_login: string}|null
     */
    public function resolveCredentialsForProject(int $projectId): ?array
    {
        $mapped = $this->integrationRepository->getActiveIntegrationsMappedByProjects([$projectId]);

        return $this->extractCredentials($mapped->get($projectId, collect()));
    }

    /**
     * @return array{token: string, client_login: string}|null
     */
    private function extractCredentials(Collection|array $integrations): ?array
    {
        $list = $integrations instanceof Collection ? $integrations : collect($integrations);

        /** @var ProjectIntegrationData|null $direct */
        $direct = $list->first(
            fn ($item) => ($item->integration->code ?? null) === 'yandex_direct'
        );

        if ($direct === null) {
            return null;
        }

        $token = $direct->settings['oauth_token']
            ?? $direct->settings['encryptedOauthToken']
            ?? null;
        $login = $direct->settings['client_login']
            ?? $direct->settings['clientLogin']
            ?? null;

        if (! filled($token) || ! filled($login)) {
            return null;
        }

        return [
            'token' => (string) $token,
            'client_login' => (string) $login,
        ];
    }

    private function makeDirectService(string $token, string $clientLogin): YandexDirectService
    {
        /** @var YandexDirectService $service */
        $service = app(YandexDirectService::class);
        $service->setupClient($token, $clientLogin);

        return $service;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveMonthPeriod(Carbon $month): array
    {
        $from = $month->copy()->startOfMonth()->startOfDay();
        $to = $month->copy()->endOfMonth()->startOfDay();

        $today = Carbon::today();
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            $to = $from->copy();
        }

        return [$from, $to];
    }

    private function putBudgetCache(int $projectId, float $value): void
    {
        Cache::put($this->budgetCacheKey($projectId), [
            'value' => $value,
            'updated_at' => Carbon::now()->toIso8601String(),
        ], self::CACHE_TTL_SECONDS);
    }

    private function budgetCacheKey(int $projectId): string
    {
        return "channels.direct.budget.{$projectId}";
    }

    /**
     * @param  array<int, int|string>  $projectIds
     * @return array<int, int>
     */
    private function limitProjectIds(array $projectIds): array
    {
        return array_slice(
            array_values(array_unique(array_map('intval', $projectIds))),
            0,
            self::BULK_MAX_PROJECTS
        );
    }
}
