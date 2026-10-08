<?php

namespace App\Services\Channels;

use App\Repositories\AgencyRepository;
use App\Repositories\ProjectRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DirectBudgetRefreshDispatcher
{
    private const GUARD_CACHE_TTL = 60 * 60 * 25;

    public function __construct(
        private readonly ChannelDirectMetricsService $directMetricsService,
        private readonly ProjectRepository $projectRepository,
        private readonly AgencyRepository $agencyRepository,
    ) {}

    public function dispatchIfDue(?Carbon $nowUtc = null): bool
    {
        $timezone = $this->agencyRepository->getPrimaryTimeZone();
        $nowLocal = ($nowUtc ?? Carbon::now('UTC'))->copy()->timezone($timezone);

        if (! $this->isRefreshWindow($nowLocal)) {
            return false;
        }

        $localDate = $nowLocal->toDateString();

        if ($this->alreadyRanToday($localDate)) {
            return false;
        }

        $projectIds = $this->projectRepository->getActiveProjectIdsWithIntegration('yandex_direct');

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

    public function resolveRefreshTime(): string
    {
        $time = $this->agencyRepository->getPrimaryDirectBudgetRefreshTime();

        return $time !== null ? substr($time, 0, 5) : '09:00';
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
}
