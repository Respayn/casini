<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Models\Project;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\IntegrationSync\YandexMetrikaSyncPeriod;
use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use App\Support\SafeLogger;
use Illuminate\Support\Carbon;
use Throwable;

class YandexMetrikaReportsCollector implements IntegrationSyncCollector
{
    public const KEY = 'yandex_metrika';

    public const INTEGRATION_CODE = 'yandex_metrika';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly YandexMetrikaReportsSync $reportsSync,
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

        $settings = $this->credentials->settingsFor($projectId, self::INTEGRATION_CODE);

        return $settings !== null && $this->reportsSync->readyReports($settings) !== [];
    }

    public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
    {
        return $this->sync(
            $context->projectId,
            fn (string $syncEnabledAt) => YandexMetrikaSyncPeriod::nightly($context->targetDate, $syncEnabledAt),
        );
    }

    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
    {
        return $this->sync(
            $projectId,
            fn (string $syncEnabledAt) => YandexMetrikaSyncPeriod::range($from, $to, $syncEnabledAt),
        );
    }

    /**
     * @param  callable(string): (array{0: Carbon, 1: Carbon}|null)  $resolvePeriod
     */
    private function sync(int $projectId, callable $resolvePeriod): IntegrationSyncResult
    {
        $settings = $this->credentials->settingsFor($projectId, self::INTEGRATION_CODE);
        $reports = $settings === null ? [] : $this->reportsSync->readyReports($settings);

        if ($reports === []) {
            return IntegrationSyncResult::failure('Нет настроенной интеграции Яндекс Метрики', requeue: false);
        }

        $period = $resolvePeriod((string) $settings['sync_enabled_at']);

        if ($period === null) {
            return IntegrationSyncResult::success();
        }

        [$from, $to] = $period;

        foreach ($reports as $report) {
            try {
                $this->reportsSync->syncReport($projectId, $settings, $report, $from, $to);
            } catch (Throwable $e) {
                $message = SafeLogger::publicMessage($e);
                SafeLogger::warning('Integration sync: Yandex Metrika report failed', [
                    'project_id' => $projectId,
                    'report' => $report,
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'message' => $message,
                ]);

                return IntegrationSyncResult::failure('Не удалось получить отчёт Яндекс Метрики: '.$message, requeue: true);
            }
        }

        return IntegrationSyncResult::success();
    }
}
