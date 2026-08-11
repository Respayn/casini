<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Helpers\PhraseDuplicateHelper;
use App\Models\Agency;
use App\Models\Project;
use App\Models\SerpPosition;
use App\Models\SerpTask;
use App\Models\YandexSearchApiDailyTopPercent;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\YandexSearchApi\YandexSearchApiSerpSyncService;
use App\Services\YandexSearchApiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class YandexSearchApiDailyPositionsCollector implements IntegrationSyncCollector
{
    public const KEY = 'yandex_search_api_daily_positions';

    public const INTEGRATION_CODE = 'yandex_search_api';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly YandexSearchApiService $searchApiService,
        private readonly YandexSearchApiSerpSyncService $serpSyncService,
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

        if (! $this->searchApiService->hasPlatformCredentials()) {
            return false;
        }

        $settings = $this->credentials->yandexSearchApi($projectId);

        return $settings !== null;
    }

    public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult
    {
        return $this->collectRange(
            $context->projectId,
            $context->targetDate->copy()->startOfDay(),
            $context->targetDate->copy()->startOfDay(),
        );
    }

    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
    {
        $settings = $this->credentials->yandexSearchApi($projectId);

        if ($settings === null) {
            return IntegrationSyncResult::failure(
                'Нет настроенной интеграции Yandex Search API',
                requeue: false,
            );
        }

        if (! $this->searchApiService->hasPlatformCredentials()) {
            return IntegrationSyncResult::failure(
                'Yandex Search API не настроен на сервере',
                requeue: false,
            );
        }

        $project = Project::query()->find($projectId);
        if ($project === null) {
            return IntegrationSyncResult::failure('Клиенто-проект не найден', requeue: false);
        }

        $domain = trim((string) ($project->domain ?? ''));
        if ($domain === '') {
            return IntegrationSyncResult::failure(
                'У клиенто-проекта не заполнен домен',
                requeue: false,
            );
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        try {
            $taskIds = $this->serpSyncService->syncFromSettings(
                $projectId,
                $settings['regions']
            );
        } catch (\Throwable $e) {
            return IntegrationSyncResult::failure(
                'Не удалось синхронизировать фразы Search API: '.$e->getMessage(),
                requeue: false,
            );
        }

        if ($taskIds === []) {
            return IntegrationSyncResult::failure(
                'Нет активных фраз для съёма Search API',
                requeue: false,
            );
        }

        $timezone = $this->resolveAgencyTimezone();
        $todayLocal = Carbon::now($timezone)->startOfDay();
        $yesterdayLocal = $todayLocal->copy()->subDay();
        $maxPhrases = (int) config('services.yandex_search_api.max_phrases_per_run', 200);

        try {
            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $dateKey = $day->toDateString();
                $canFetchApi = $day->copy()->startOfDay()->gte($yesterdayLocal);

                if ($canFetchApi) {
                    $this->fetchPositionsForDay($projectId, $taskIds, $dateKey, $domain, $maxPhrases);
                }

                $this->upsertDailyAggregate($projectId, $dateKey);
            }

            return IntegrationSyncResult::success();
        } catch (RequestException $e) {
            Log::warning('Integration sync: Yandex Search API rate/server error', [
                'project_id' => $projectId,
                'message' => $e->getMessage(),
            ]);

            return IntegrationSyncResult::failure(
                'Yandex Search API временно недоступен: '.$e->getMessage(),
                requeue: true,
            );
        } catch (\Throwable $e) {
            Log::warning('Integration sync: Yandex Search API range failed', [
                'project_id' => $projectId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'message' => $e->getMessage(),
            ]);

            return IntegrationSyncResult::failure(
                'Не удалось снять позиции Search API: '.$e->getMessage(),
                requeue: true,
            );
        }
    }

    /**
     * @param  list<int>  $taskIds
     */
    private function fetchPositionsForDay(
        int $projectId,
        array $taskIds,
        string $dateKey,
        string $domain,
        int $maxPhrases,
    ): void {
        $tasks = SerpTask::query()
            ->with(['keyword', 'region'])
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->whereIn('id', $taskIds)
            ->orderBy('id')
            ->get();

        if ($tasks->count() > $maxPhrases) {
            Log::warning('Yandex Search API: phrase cap reached', [
                'project_id' => $projectId,
                'tasks' => $tasks->count(),
                'cap' => $maxPhrases,
            ]);
            $tasks = $tasks->take($maxPhrases);
        }

        foreach ($tasks as $task) {
            $phrase = (string) ($task->keyword?->phrase ?? '');
            $regionId = (int) ($task->region?->geo_id ?? 0);

            if ($phrase === '' || $regionId <= 0) {
                continue;
            }

            try {
                $results = $this->searchApiService->searchWeb($phrase, $regionId);
                $position = $this->searchApiService->resolvePositionForDomain($results, $domain);
                $matchedUrl = null;

                if ($position !== null) {
                    foreach ($results as $item) {
                        if ((int) ($item['position'] ?? 0) === $position) {
                            $matchedUrl = (string) ($item['url'] ?? '');
                            break;
                        }
                    }
                }

                SerpPosition::query()->updateOrCreate(
                    [
                        'serp_task_id' => $task->id,
                        'check_date' => $dateKey,
                    ],
                    [
                        'position' => $position,
                        'url' => $matchedUrl !== '' ? $matchedUrl : null,
                    ]
                );

                $task->update(['last_check_at' => now()]);
            } catch (RequestException $e) {
                throw $e;
            } catch (\Throwable $e) {
                Log::warning('Yandex Search API: phrase fetch failed', [
                    'project_id' => $projectId,
                    'task_id' => $task->id,
                    'phrase' => $phrase,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function upsertDailyAggregate(int $projectId, string $dateKey): void
    {
        $positions = SerpPosition::query()
            ->whereDate('check_date', $dateKey)
            ->whereHas('task', function ($q) use ($projectId) {
                $q->where('project_id', $projectId)->where('is_active', true);
            })
            ->get(['position']);

        if ($positions->isEmpty()) {
            return;
        }

        $total = $positions->count();
        $top10 = $positions->whereNotNull('position')->filter(fn ($row) => (int) $row->position <= 10)->count();
        $percent = $total > 0 ? round($top10 / $total * 100, 1) : 0.0;

        YandexSearchApiDailyTopPercent::upsertDaily($projectId, $dateKey, $percent, $total);
    }

    private function resolveAgencyTimezone(): string
    {
        $timezone = Agency::query()->orderBy('id')->value('time_zone');

        return filled($timezone) ? (string) $timezone : (string) config('app.timezone', 'UTC');
    }
}
