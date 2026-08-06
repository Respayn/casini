<?php

namespace App\Services\IntegrationSync;

use App\Contracts\IntegrationSyncCollector;
use App\Enums\IntegrationSyncItemStatus;
use App\Enums\IntegrationSyncRunStatus;
use App\Jobs\ProcessIntegrationSyncItem;
use App\Models\Agency;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use App\Models\Project;
use App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector;
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

            $projectIds = $this->candidateProjectIds();
            $position = 0;

            foreach ($projectIds as $projectId) {
                foreach ($this->collectors as $collector) {
                    $item = IntegrationSyncItem::query()->create([
                        'run_id' => $run->id,
                        'project_id' => $projectId,
                        'collector' => $collector->key(),
                        'status' => IntegrationSyncItemStatus::Pending,
                        'attempts' => 0,
                        'position' => $position++,
                    ]);

                    ProcessIntegrationSyncItem::dispatch($item->id);
                }
            }

            if ($projectIds->isEmpty()) {
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
        return (int) $nowLocal->format('G') === self::DISPATCH_LOCAL_HOUR
            && (int) $nowLocal->format('i') === self::DISPATCH_LOCAL_MINUTE;
    }

    /**
     * Активные проекты с включённым Яндекс.Директом и credentials.
     *
     * @return Collection<int, int>
     */
    public function candidateProjectIds(): Collection
    {
        $directIntegrationId = Integration::query()
            ->where('code', 'yandex_direct')
            ->value('id');

        if ($directIntegrationId === null) {
            return collect();
        }

        return IntegrationProject::query()
            ->where('integration_id', $directIntegrationId)
            ->where('is_enabled', true)
            ->whereIn('project_id', Project::query()->where('is_active', true)->select('id'))
            ->get(['project_id', 'settings'])
            ->filter(function (IntegrationProject $row) {
                $settings = $row->settings;
                if (! is_array($settings)) {
                    return false;
                }

                $token = $settings['oauth_token'] ?? $settings['encryptedOauthToken'] ?? null;
                $login = $settings['client_login'] ?? $settings['clientLogin'] ?? null;

                return filled($token) && filled($login);
            })
            ->pluck('project_id')
            ->unique()
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
    public static function defaultCollectors(): array
    {
        return [
            app(YandexDirectDailySpendCollector::class),
        ];
    }
}
