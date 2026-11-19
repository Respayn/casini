<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Repositories\ProjectRepository;
use App\Services\GoogleSheetsService;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Support\SafeLogger;
use Illuminate\Support\Carbon;
use Throwable;

class GoogleSheetsSpendingsCollector implements IntegrationSyncCollector
{
    public const KEY = 'google_sheets';

    public const INTEGRATION_CODE = 'google_sheets';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly GoogleSheetsService $googleSheetsService,
        private readonly ProjectRepository $projects,
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

        if (! $this->credentials->hasEnabled($projectId, self::INTEGRATION_CODE)) {
            return false;
        }

        return $this->googleSheetsService->resolveSpreadsheetIdForProject($projectId) !== '';
    }

    /**
     * Ночью обновляется открытый месяц агентства; закрытые месяцы не трогаем.
     */
    public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
    {
        return $this->run(
            $context->projectId,
            fn () => $this->googleSheetsService->syncProjectOpenMonth($context->projectId),
        );
    }

    /**
     * Ручное обновление и backfill: месяц конца периода (его показывают Каналы).
     */
    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
    {
        return $this->run(
            $projectId,
            fn () => $this->googleSheetsService->syncProjectMonth($projectId, $to->copy()->startOfMonth()),
        );
    }

    private function run(int $projectId, callable $sync): IntegrationSyncResult
    {
        try {
            $sync();

            return IntegrationSyncResult::success();
        } catch (Throwable $e) {
            $message = SafeLogger::publicMessage($e);
            SafeLogger::warning('Integration sync: Google Sheets spendings failed', [
                'project_id' => $projectId,
                'message' => $message,
            ]);

            return IntegrationSyncResult::failure('Не удалось получить расходы Google Таблиц: '.$message, requeue: true);
        }
    }
}
