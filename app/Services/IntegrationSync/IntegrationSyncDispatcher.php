<?php

namespace App\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use App\Enums\IntegrationSyncItemStatus;
use App\Enums\IntegrationSyncRunStatus;
use App\Jobs\ProcessIntegrationSyncItem;
use App\Models\Agency;
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use App\Models\Project;
use App\Services\IntegrationSync\Collectors\CallibriDailyLeadsCollector;
use App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector;
use App\Services\IntegrationSync\Collectors\YandexSearchApiDailyPositionsCollector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntegrationSyncDispatcher
{
    public const MAX_ATTEMPTS = 3;

    /** Локальная минута старта ночного съёма (00:01). */
    public const DISPATCH_LOCAL_HOUR = 0;

    public const DISPATCH_LOCAL_MINUTE = 1;

    /**
     * @param  array<int, IntegrationSyncCollector>  $collectors
     */
    public function __construct(
        private readonly array $collectors,
    ) {}

    /**
     * Вызывается каждую минуту из schedule.
     */
    public function dispatchIfDue(?Carbon $nowUtc = null): ?IntegrationSyncRun
    {
        $timezone = $this->resolveAgencyTimezone();
        $nowLocal = ($nowUtc ?? Carbon::now('UTC'))->copy()->timezone($timezone);

        if (! $this->isDispatchWindow($nowLocal)) {
            return null;
        }

        $localDate = $nowLocal->toDateString();
        $targetDate = $nowLocal->copy()->subDay()->toDateString();

        if (IntegrationSyncRun::query()
            ->whereDate('local_date', $localDate)
            ->where('timezone', $timezone)
            ->exists()
        ) {
            return null;
        }

        return $this->startRun($localDate, $timezone, $targetDate);
    }

    public function startRun(string $localDate, string $timezone, string $targetDate): IntegrationSyncRun
    {
        return DB::transaction(function () use ($localDate, $timezone, $targetDate) {
            $run = IntegrationSyncRun::query()->create([
                'local_date' => $localDate,
                'timezone' => $timezone,
                'target_date' => $targetDate,
                'status' => IntegrationSyncRunStatus::Running,
                'started_at' => now(),
            ]);

            $projectIds = $this->activeProjectIds();
            $position = 0;
            $itemsCreated = 0;

            foreach ($this->collectors as $collector) {
                foreach ($projectIds as $projectId) {
                    try {
                        if (! $collector->supportsProject((int) $projectId)) {
                            continue;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Integration sync: supportsProject failed', [
                            'collector' => $collector->key(),
                            'project_id' => $projectId,
                            'message' => $e->getMessage(),
                        ]);

                        continue;
                    }

                    $item = IntegrationSyncItem::query()->create([
                        'run_id' => $run->id,
                        'project_id' => $projectId,
                        'collector' => $collector->key(),
                        'status' => IntegrationSyncItemStatus::Pending,
                        'attempts' => 0,
                        'position' => $position++,
                    ]);

                    ProcessIntegrationSyncItem::dispatch($item->id);
                    $itemsCreated++;
                }
            }

            if ($itemsCreated === 0) {
                $run->update([
                    'status' => IntegrationSyncRunStatus::Completed,
                    'finished_at' => now(),
                ]);
            }

            Log::info('Integration sync run started', [
                'run_id' => $run->id,
                'local_date' => $localDate,
                'target_date' => $targetDate,
                'timezone' => $timezone,
                'projects' => $projectIds->count(),
                'items' => $itemsCreated,
            ]);

            return $run;
        });
    }

    public function resolveAgencyTimezone(): string
    {
        $timezone = Agency::query()->orderBy('id')->value('time_zone');

        if (filled($timezone)) {
            return (string) $timezone;
        }

        return (string) config('app.timezone', 'UTC');
    }

    public function isDispatchWindow(Carbon $nowLocal): bool
    {
        $minutesFromMidnight = ((int) $nowLocal->format('G')) * 60 + (int) $nowLocal->format('i');
        $windowStart = self::DISPATCH_LOCAL_HOUR * 60 + self::DISPATCH_LOCAL_MINUTE;

        return $minutesFromMidnight >= $windowStart;
    }

    /**
     * Активные клиенто-проекты (фильтр интеграции — на collector.supportsProject).
     *
     * @return Collection<int, int>
     */
    public function activeProjectIds(): Collection
    {
        return Project::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id');
    }

    /**
     * @deprecated use activeProjectIds + collector.supportsProject
     *
     * @return Collection<int, int>
     */
    public function candidateProjectIds(): Collection
    {
        $direct = $this->collector(YandexDirectDailySpendCollector::KEY);

        if ($direct === null) {
            return collect();
        }

        return $this->activeProjectIds()
            ->filter(fn (int $projectId) => $direct->supportsProject($projectId))
            ->values();
    }

    public function collector(string $key): ?IntegrationSyncCollector
    {
        foreach ($this->collectors as $collector) {
            if ($collector->key() === $key) {
                return $collector;
            }
        }

        return null;
    }

    /**
     * @return array<int, IntegrationSyncCollector>
     */
    public function collectors(): array
    {
        return $this->collectors;
    }

    /**
     * @return array<int, IntegrationSyncCollector>
     */
    public static function defaultCollectors(): array
    {
        return [
            app(YandexDirectDailySpendCollector::class),
            app(CallibriDailyLeadsCollector::class),
            app(YandexSearchApiDailyPositionsCollector::class),
        ];
    }
}
