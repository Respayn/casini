<?php

namespace App\Services\IntegrationSync;

use App\Models\IntegrationSyncRun;
use App\Repositories\AgencyRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class IntegrationManualRefreshTimestamp
{
    public function __construct(
        private readonly AgencyRepository $agencyRepository = new AgencyRepository,
    ) {}

    public function record(int $agencyId): void
    {
        Cache::forever(
            $this->cacheKey($agencyId),
            Carbon::now()->toIso8601String(),
        );
    }

    public function formattedLabel(int $agencyId): ?string
    {
        $at = $this->resolveLatestAt($agencyId);

        if ($at === null) {
            return null;
        }

        return $at
            ->timezone($this->agencyRepository->getPrimaryTimeZone())
            ->format('H:i, d.m.y');
    }

    private function resolveLatestAt(int $agencyId): ?Carbon
    {
        $candidates = [];

        $manual = $this->manualAt($agencyId);
        if ($manual !== null) {
            $candidates[] = $manual;
        }

        $automatic = $this->lastAutomaticSyncAt();
        if ($automatic !== null) {
            $candidates[] = $automatic;
        }

        if ($candidates === []) {
            return null;
        }

        $latest = $candidates[0];
        foreach (array_slice($candidates, 1) as $candidate) {
            if ($candidate->gt($latest)) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    private function manualAt(int $agencyId): ?Carbon
    {
        $raw = Cache::get($this->cacheKey($agencyId));

        if (! filled($raw)) {
            return null;
        }

        try {
            return Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function lastAutomaticSyncAt(): ?Carbon
    {
        $finishedAt = IntegrationSyncRun::query()
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->value('finished_at');

        if ($finishedAt === null) {
            return null;
        }

        return Carbon::parse($finishedAt);
    }

    private function cacheKey(int $agencyId): string
    {
        return "integrations.manual_refresh.last_at.agency.{$agencyId}";
    }
}
