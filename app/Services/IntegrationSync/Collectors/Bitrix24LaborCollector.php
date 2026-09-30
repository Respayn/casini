<?php

namespace App\Services\IntegrationSync\Collectors;

use App\Contracts\IntegrationSyncCollector;
use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use App\Models\Bitrix24DailyLabor;
use App\Models\Project;
use App\Models\User;
use App\Services\Bitrix24\Bitrix24ApiException;
use App\Services\Bitrix24\Bitrix24Client;
use App\Services\Bitrix24\Bitrix24LaborRoleResolver;
use App\Services\Bitrix24\Bitrix24TaskSelection;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\Rates\UserRateHistory;
use App\Support\SafeLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Bitrix24LaborCollector implements IntegrationSyncCollector
{
    public const KEY = 'bitrix24_labor';

    public const INTEGRATION_CODE = 'bitrix24';

    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly Bitrix24Client $client,
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

        return $this->credentials->bitrix24($projectId) !== null;
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
     * Часы за период: строки проекта за эти дни перезаписываются целиком.
     */
    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult
    {
        $settings = $this->credentials->bitrix24($projectId);

        if ($settings === null) {
            return IntegrationSyncResult::failure('Нет настроенной интеграции Битрикс24', requeue: false);
        }

        $project = Project::query()->with('client')->find($projectId);

        if ($project === null) {
            return IntegrationSyncResult::failure('Клиенто-проект не найден', requeue: false);
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        try {
            $secondsByDay = $this->fetchSeconds($settings, $fromDate, $toDate);
            $rows = $this->buildRows($project, $secondsByDay);

            DB::transaction(function () use ($projectId, $fromDate, $toDate, $rows) {
                Bitrix24DailyLabor::query()
                    ->where('project_id', $projectId)
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->delete();

                if ($rows !== []) {
                    Bitrix24DailyLabor::query()->insert($rows);
                }
            });

            return IntegrationSyncResult::success();
        } catch (Bitrix24ApiException $e) {
            return $this->failure($projectId, $fromDate, $toDate, $e->getMessage());
        } catch (Throwable $e) {
            return $this->failure($projectId, $fromDate, $toDate, SafeLogger::publicMessage($e));
        }
    }

    /**
     * @param  array{webhook: string, root_task_id: int, search_query: string, timezone: string}  $settings
     * @return array<string, array<string, array<int, int>>> день => месяц отчёта => [ID сотрудника в Битрикс24 => секунды]
     */
    private function fetchSeconds(array $settings, string $fromDate, string $toDate): array
    {
        $timezone = $settings['timezone'];
        $periodStart = Carbon::parse($fromDate, $timezone)->startOfDay();
        $periodEnd = Carbon::parse($toDate, $timezone)->endOfDay();

        $tasks = array_filter(
            $this->client->descendantTasks($settings['webhook'], $settings['root_task_id']),
            fn (array $task) => Bitrix24TaskSelection::titleMatches($task['title'], $settings['search_query']),
        );

        $secondsByDay = [];

        foreach ($tasks as $task) {
            foreach ($this->client->elapsedItems($settings['webhook'], $task['id'], $periodStart, $periodEnd) as $item) {
                $workDate = $item['created_at']->copy()->timezone($timezone);
                $day = $workDate->toDateString();

                if ($day < $fromDate || $day > $toDate) {
                    continue;
                }

                $month = Bitrix24TaskSelection::reportMonth($task['title'], $workDate)->toDateString();
                $secondsByDay[$day][$month][$item['user_id']] = ($secondsByDay[$day][$month][$item['user_id']] ?? 0) + $item['seconds'];
            }
        }

        return $secondsByDay;
    }

    /**
     * @param  array<string, array<string, array<int, int>>>  $secondsByDay
     * @return list<array<string, mixed>>
     */
    private function buildRows(Project $project, array $secondsByDay): array
    {
        $bitrixIds = collect($secondsByDay)
            ->flatMap(fn (array $byMonth) => collect($byMonth)->flatMap(fn (array $byUser) => array_keys($byUser)))
            ->unique()
            ->values();

        if ($bitrixIds->isEmpty()) {
            return [];
        }

        $userIdsByBitrixId = User::query()
            ->whereIn('bitrix24_id', $bitrixIds)
            ->pluck('id', 'bitrix24_id')
            ->mapWithKeys(fn ($id, $bitrixId) => [(int) $bitrixId => (int) $id]);

        $rates = UserRateHistory::forUsers($userIdsByBitrixId->values());
        $assistantIds = $this->assistantIds($project->id);
        $specialistId = $project->specialist_id !== null ? (int) $project->specialist_id : null;
        $managerId = $project->client?->manager_id !== null ? (int) $project->client->manager_id : null;
        $now = now();
        $rows = [];

        foreach ($secondsByDay as $day => $byMonth) {
            foreach ($byMonth as $month => $byUser) {
                foreach ($byUser as $bitrixId => $seconds) {
                    $userId = $userIdsByBitrixId->get((int) $bitrixId);

                    if ($userId === null) {
                        continue;
                    }

                    $role = Bitrix24LaborRoleResolver::resolve(
                        $userId,
                        $rates->rateAt($userId, $day)['name'] ?? null,
                        $specialistId,
                        $managerId,
                        $assistantIds,
                    );

                    if ($role === null) {
                        continue;
                    }

                    $rows[] = [
                        'project_id' => $project->id,
                        'date' => $day,
                        'report_month' => $month,
                        'user_id' => $userId,
                        'role' => $role->value,
                        'seconds' => $seconds,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @return list<int>
     */
    private function assistantIds(int $projectId): array
    {
        if (! Schema::hasTable('project_assistant')) {
            return [];
        }

        return DB::table('project_assistant')
            ->where('project_id', $projectId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function failure(int $projectId, string $fromDate, string $toDate, string $message): IntegrationSyncResult
    {
        SafeLogger::warning('Integration sync: Bitrix24 labor failed', [
            'project_id' => $projectId,
            'from' => $fromDate,
            'to' => $toDate,
            'message' => $message,
        ]);

        return IntegrationSyncResult::failure('Не удалось получить часы Битрикс24: '.$message, requeue: true);
    }
}
