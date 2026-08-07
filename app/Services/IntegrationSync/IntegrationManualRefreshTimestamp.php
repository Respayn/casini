<?php

namespace App\Services\IntegrationSync;

use App\Models\Agency;
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
        $raw = Cache::get($this->cacheKey($userId, $product));

        if (! filled($raw)) {
            return null;
        }

        try {
            return Carbon::parse((string) $raw)
                ->timezone($this->agencyTimezone())
                ->format('H:i, d.m.y');
        } catch (\Throwable) {
            return null;
        }
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
