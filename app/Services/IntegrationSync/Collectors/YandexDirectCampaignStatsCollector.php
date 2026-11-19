<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Repositories\ProjectRepository;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\YandexDirectService;
use App\Support\SafeLogger;
use Illuminate\Support\Carbon;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;

class YandexDirectCampaignStatsCollector implements IntegrationSyncCollector
{
    public const KEY = 'yandex_direct_campaign_stats';

    public const INTEGRATION_CODE = 'yandex_direct';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly ProjectRepository $projects,
        private readonly YandexDirectRepositoryInterface $yandexDirect,
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
        if (! $this->projects->isActive($projectId)) {
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
     * Съём статистики кампаний за период: 2 запроса к API (с НДС / без),
     * замена строк за дни периода в yandex_direct_campaign_stats.
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
        $token = $credentials['token'];
        $clientLogin = $credentials['client_login'];
        $retriedAfterRefresh = false;

        try {
            while (true) {
                try {
                    $withVat = $this->fetchCampaignRows($token, $clientLogin, $from, $to, true);
                    $withoutVat = $this->fetchCampaignRows($token, $clientLogin, $from, $to, false);
                    break;
                } catch (\Throwable $e) {
                    if ($retriedAfterRefresh || ! SafeLogger::isYandexDirectAuthError($e)) {
                        throw $e;
                    }

                    $refreshed = $this->credentials->refreshYandexDirectAccessToken($projectId);

                    if (! filled($refreshed)) {
                        throw $e;
                    }

                    $token = $refreshed;
                    $retriedAfterRefresh = true;
                }
            }

            $costWithoutVatByKey = [];
            foreach ($withoutVat as $row) {
                $key = $row['date'].'|'.$row['campaign_id'];
                $costWithoutVatByKey[$key] = round((float) $row['cost'], 2);
            }

            $now = now();
            $insert = [];
            foreach ($withVat as $row) {
                $key = $row['date'].'|'.$row['campaign_id'];
                $insert[] = [
                    'project_id' => $projectId,
                    'campaign_name' => $row['campaign_name'] !== '' ? $row['campaign_name'] : ('Кампания '.$row['campaign_id']),
                    'campaign_id' => $row['campaign_id'],
                    'impressions' => $row['impressions'],
                    'clicks' => $row['clicks'],
                    'cost_with_vat' => round((float) $row['cost'], 2),
                    'cost_without_vat' => $costWithoutVatByKey[$key] ?? 0.0,
                    'conversions' => $row['conversions'],
                    'goal_name' => null,
                    'date' => $row['date'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->yandexDirect->replaceCampaignStatsForPeriod(
                $projectId,
                $from->toDateString(),
                $to->toDateString(),
                $insert,
            );

            return IntegrationSyncResult::success();
        } catch (\Throwable $e) {
            SafeLogger::warning('Integration sync: Yandex Direct campaign stats range failed', [
                'project_id' => $projectId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'message' => SafeLogger::publicMessage($e),
            ]);

            return IntegrationSyncResult::failure(
                'Не удалось получить статистику кампаний Директа: '.SafeLogger::publicMessage($e),
                requeue: true,
            );
        }
    }

    /**
     * @return list<array{
     *     date: string,
     *     campaign_id: int,
     *     campaign_name: string,
     *     impressions: int,
     *     clicks: int,
     *     cost: float,
     *     conversions: int
     * }>
     */
    private function fetchCampaignRows(
        string $token,
        string $clientLogin,
        Carbon $from,
        Carbon $to,
        bool $includeVat
    ): array {
        /** @var YandexDirectService $service */
        $service = app(YandexDirectService::class);
        $service->setupClient($token, $clientLogin);

        return $service->getCampaignDailyPerformance($from->copy(), $to->copy(), $includeVat);
    }
}
