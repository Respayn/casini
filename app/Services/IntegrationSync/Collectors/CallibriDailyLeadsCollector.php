<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Models\CallibriDailyLeadCount;
use App\Models\Project;
use App\Services\CallibriService;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CallibriDailyLeadsCollector implements IntegrationSyncCollector
{
    public const KEY = 'callibri_daily_leads';

    public const INTEGRATION_CODE = 'callibri';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly CallibriService $callibriService,
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

        return $this->credentials->callibri($projectId) !== null;
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
     * Съём лидов за период: сырые записи в callibri_leads + дневной агрегат (в т.ч. 0).
     */
    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
    {
        if ($this->credentials->callibri($projectId) === null) {
            return IntegrationSyncResult::failure(
                'Нет настроенной интеграции Callibri',
                requeue: false,
            );
        }

        $project = Project::query()->find($projectId);

        if ($project === null) {
            return IntegrationSyncResult::failure(
                'Клиенто-проект не найден',
                requeue: false,
            );
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        try {
            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $leads = $this->callibriService->getAndSaveLeadsByDay($project, $day->copy());

                CallibriDailyLeadCount::query()->updateOrCreate(
                    [
                        'project_id' => $projectId,
                        'date' => $day->toDateString(),
                    ],
                    [
                        'leads_count' => $leads->count(),
                    ]
                );
            }

            return IntegrationSyncResult::success();
        } catch (\Throwable $e) {
            Log::warning('Integration sync: Callibri daily leads range failed', [
                'project_id' => $projectId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'message' => $e->getMessage(),
            ]);

            return IntegrationSyncResult::failure(
                'Не удалось получить лиды Callibri: '.$e->getMessage(),
                requeue: true,
            );
        }
    }
}
