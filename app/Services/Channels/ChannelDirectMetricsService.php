<?php

namespace App\Services\Channels;

use App\Models\YandexDirectDailySpending;
use App\Repositories\AgencyRepository;
use App\Repositories\IntegrationRepository;
use App\Services\YandexDirectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChannelDirectMetricsService
{
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    private ?string $agencyTimezone = null;

    public function __construct(
        private readonly IntegrationRepository $integrationRepository,
        private readonly AgencyRepository $agencyRepository = new AgencyRepository,
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
     * Сумма дневных расходов из БД за период (источник правды после ночного съёма).
     */
    public function getStoredSpendings(
        int $projectId,
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $includeVat
    ): ?float {
        [$from, $to] = $this->resolvePeriod($periodFrom, $periodTo);

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
     * Принудительный съём бюджетов без consume throttle (после одного consume у оркестратора).
     *
     * @param  array<int, int|string>  $projectIds
     * @return array{updated: int, failed: int, skipped: int}
     */
    public function refreshBudgetsForcedWithoutThrottle(array $projectIds): array
    {
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0];

        $ids = array_values(array_unique(array_map('intval', $projectIds)));

        foreach ($ids as $projectId) {
            if ($projectId <= 0) {
                continue;
            }

            $result = $this->refreshBudgetForcedWithoutThrottle($projectId);

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
     * @return array{value: ?float, updatedAt: ?Carbon, projectId: int, canRefresh: bool}
     */
    public function budgetCellParams(int $projectId, Collection|array $integrations): array
    {
        $payload = $this->getCachedBudgetPayload($projectId);
        $updatedAt = $payload['updatedAt'];
        if ($updatedAt !== null) {
            $updatedAt = $updatedAt->copy()->timezone($this->resolveAgencyTimezone());
        }

        return [
            'value' => $payload['value'],
            'updatedAt' => $updatedAt,
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
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $includeVat
    ): array {
        return [
            'value' => $this->getStoredSpendings($projectId, $periodFrom, $periodTo, $includeVat),
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
    public function resolvePeriod(Carbon $periodFrom, Carbon $periodTo): array
    {
        $from = $periodFrom->copy()->startOfMonth()->startOfDay();
        $to = $periodTo->copy()->endOfMonth()->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfMonth()->startOfDay(), $from->copy()->endOfMonth()->startOfDay()];
        }

        $today = Carbon::today();
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            $to = $from->copy();
        }

        return [$from, $to];
    }

    private function resolveAgencyTimezone(): string
    {
        if ($this->agencyTimezone !== null) {
            return $this->agencyTimezone;
        }

        return $this->agencyTimezone = $this->agencyRepository->getPrimaryTimeZone();
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
}
