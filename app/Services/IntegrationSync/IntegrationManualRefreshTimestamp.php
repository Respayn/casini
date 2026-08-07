<?php

namespace App\Services\IntegrationSync;

use App\Models\Agency;
use App\Models\IntegrationSyncRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class IntegrationManualRefreshTimestamp
{
    public function record(int $userId, string $product): void
    {
        Cache::forever(
            $this->cacheKey($userId, $product),
            Carbon::now()->toIso8601String(),
        );
    }

    public function formattedLabel(int $userId, string $product): ?string
    {
        $at = $this->resolveLatestAt($userId, $product);

        if ($at === null) {
            return null;
        }

        return $at
            ->timezone($this->agencyTimezone())
            ->format('H:i, d.m.y');
    }

    private function resolveLatestAt(int $userId, string $product): ?Carbon
    {
        $candidates = [];

        $manual = $this->manualAt($userId, $product);
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

    private function manualAt(int $userId, string $product): ?Carbon
    {
        $raw = Cache::get($this->cacheKey($userId, $product));

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

    private function cacheKey(int $userId, string $product): string
    {
        return "integrations.manual_refresh.last_at.user.{$userId}.{$product}";
    }

    private function agencyTimezone(): string
    {
        $timezone = Agency::query()->orderBy('id')->value('time_zone');

        return filled($timezone)
            ? (string) $timezone
            : (string) config('app.timezone', 'UTC');
    }
}
