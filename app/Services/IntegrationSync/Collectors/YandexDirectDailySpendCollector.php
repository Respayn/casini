<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Models\Project;
use App\Models\YandexDirectDailySpending;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\YandexDirectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class YandexDirectDailySpendCollector implements IntegrationSyncCollector
{
    public const KEY = 'yandex_direct_daily_spend';

    public const INTEGRATION_CODE = 'yandex_direct';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
    ) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function integrationCode(): string
    {
        return self::INTEGRATION_CODE;
    }

    public function supportsProject(int $projectId): bool
    {
        if (! Project::query()->whereKey($projectId)->where('is_active', true)->exists()) {
            return false;
        }

        return $this->credentials->yandexDirect($projectId) !== null;
    }

    public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
    {
        return $this->collectRange(
            $context->projectId,
            $context->targetDate->copy()->startOfDay(),
            $context->targetDate->copy()->startOfDay(),
        );
    }

    /**
     * Съём расхода за период: 2 запроса к API (с НДС / без), upsert по дням.
     * Дни без строк в отчёте записываются как 0 — чтобы повторный клик не бил в API.
     */
    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
    {
        $credentials = $this->credentials->yandexDirect($projectId);

        if ($credentials === null) {
            return IntegrationSyncResult::failure(
                'Нет настроенной интеграции Яндекс.Директ',
                requeue: false,
            );
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        try {
            $withVatByDay = $this->fetchDailyCosts(
                $credentials['token'],
                $credentials['client_login'],
                $from,
                $to,
                true,
            );
            $withoutVatByDay = $this->fetchDailyCosts(
                $credentials['token'],
                $credentials['client_login'],
                $from,
                $to,
                false,
            );

            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $key = $day->toDateString();
                YandexDirectDailySpending::query()->updateOrCreate(
                    [
                        'project_id' => $projectId,
                        'date' => $key,
                    ],
                    [
                        'cost_with_vat' => round((float) ($withVatByDay[$key] ?? 0), 2),
                        'cost_without_vat' => round((float) ($withoutVatByDay[$key] ?? 0), 2),
                    ]
                );
            }

            return IntegrationSyncResult::success();
        } catch (\Throwable $e) {
            Log::warning('Integration sync: Yandex Direct daily spend range failed', [
                'project_id' => $projectId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'message' => $e->getMessage(),
            ]);

            return IntegrationSyncResult::failure(
                'Не удалось получить расход в Директе: '.$e->getMessage(),
                requeue: true,
            );
        }
    }

    /**
     * @return array<string, float>
     */
    private function fetchDailyCosts(
        string $token,
        string $clientLogin,
        Carbon $from,
        Carbon $to,
        bool $includeVat
    ): array {
        /** @var YandexDirectService $service */
        $service = app(YandexDirectService::class);
        $service->setupClient($token, $clientLogin);

        return $service->getDailyProjectExpenses($from->copy(), $to->copy(), $includeVat);
    }
}
