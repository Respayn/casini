<?php

namespace App\Services\Channels;

use App\Models\Agency;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DirectBudgetRefreshDispatcher
{
    private const GUARD_CACHE_TTL = 60 * 60 * 25;

    public function __construct(
        private readonly ChannelDirectMetricsService $directMetricsService,
    ) {}

    public function dispatchIfDue(?Carbon $nowUtc = null): bool
    {
        $timezone = $this->resolveAgencyTimezone();
        $nowLocal = ($nowUtc ?? Carbon::now('UTC'))->copy()->timezone($timezone);

        if (! $this->isRefreshWindow($nowLocal)) {
            return false;
        }

        $localDate = $nowLocal->toDateString();

        if ($this->alreadyRanToday($localDate)) {
            return false;
        }

        $projectIds = $this->activeProjectIdsWithDirect();

        if ($projectIds === []) {
            $this->markRanToday($localDate);

            return false;
        }

        Log::info('Direct budget scheduled refresh started', [
            'local_date' => $localDate,
            'timezone' => $timezone,
            'projects' => count($projectIds),
        ]);

        $this->directMetricsService->refreshBudgetsForcedWithoutThrottle($projectIds);
        $this->markRanToday($localDate);

        return true;
    }

    public function resolveAgencyTimezone(): string
    {
        $timezone = Agency::query()->orderBy('id')->value('time_zone');

        return filled($timezone) ? (string) $timezone : (string) config('app.timezone', 'UTC');
    }

    public function resolveRefreshTime(): string
    {
        $time = Agency::query()->orderBy('id')->value('direct_budget_refresh_time');

        if (filled($time)) {
            return substr((string) $time, 0, 5);
        }

        return '09:00';
    }

    public function isRefreshWindow(Carbon $nowLocal): bool
    {
        $configured = $this->resolveRefreshTime();
        $current = $nowLocal->format('H:i');

        return $current === $configured;
    }

    private function alreadyRanToday(string $localDate): bool
    {
        return Cache::has($this->guardCacheKey($localDate));
    }

    private function markRanToday(string $localDate): void
    {
        Cache::put($this->guardCacheKey($localDate), true, self::GUARD_CACHE_TTL);
    }

    private function guardCacheKey(string $localDate): string
    {
        return "channels.direct.budget.scheduled.{$localDate}";
    }

    /**
     * @return list<int>
     */
    private function activeProjectIdsWithDirect(): array
    {
        return Project::query()
            ->where('is_active', true)
            ->whereHas('integrations', fn ($q) => $q->where('code', 'yandex_direct'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
