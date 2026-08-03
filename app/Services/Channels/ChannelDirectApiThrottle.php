<?php

namespace App\Services\Channels;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Антиспам запросов к API Яндекс.Директ из Каналов (клик / bulk).
 *
 * Правила:
 * - не чаще 1 раза в 5 минут;
 * - не более 3 успешных «заходов» подряд;
 * - после 3-го — блокировка на 60 минут.
 */
class ChannelDirectApiThrottle
{
    public const COOLDOWN_SECONDS = 300;

    public const MAX_ATTEMPTS = 3;

    public const BLOCK_SECONDS = 3600;

    /**
     * @return array{ok: bool, error: ?string}
     */
    public function consume(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        if ($userId === null) {
            return ['ok' => false, 'error' => 'Нужна авторизация'];
        }

        $key = $this->cacheKey((int) $userId);
        $state = $this->readState($key);
        $now = Carbon::now();

        if ($state['blocked_until'] !== null && $now->lt($state['blocked_until'])) {
            $minutes = $this->minutesRemaining($now, $state['blocked_until']);

            return [
                'ok' => false,
                'error' => "Лимит запросов к Яндекс.Директу исчерпан. Повторите через {$minutes} мин.",
            ];
        }

        if ($state['blocked_until'] !== null && $now->gte($state['blocked_until'])) {
            $state = $this->emptyState();
        }

        if ($state['last_at'] !== null) {
            $elapsed = $this->elapsedSeconds($state['last_at'], $now);

            if ($elapsed < self::COOLDOWN_SECONDS) {
                $wait = self::COOLDOWN_SECONDS - $elapsed;
                $minutes = max(1, (int) ceil($wait / 60));
                // Не больше длины кулдауна (защита от знака diffInSeconds)
                $minutes = min($minutes, (int) ceil(self::COOLDOWN_SECONDS / 60));

                return [
                    'ok' => false,
                    'error' => "Запрос к Директу можно повторить не чаще раза в 5 минут. Подождите ещё {$minutes} мин.",
                ];
            }

            // Долгий перерыв — начинаем серию заново
            if ($elapsed >= self::BLOCK_SECONDS) {
                $state['attempts'] = 0;
            }
        }

        $state['attempts']++;
        $state['last_at'] = $now;

        if ($state['attempts'] >= self::MAX_ATTEMPTS) {
            $state['blocked_until'] = $now->copy()->addSeconds(self::BLOCK_SECONDS);
            $state['attempts'] = 0;
        } else {
            $state['blocked_until'] = null;
        }

        $this->writeState($key, $state);

        return ['ok' => true, 'error' => null];
    }

    private function elapsedSeconds(Carbon $from, Carbon $to): int
    {
        return max(0, $to->getTimestamp() - $from->getTimestamp());
    }

    private function minutesRemaining(Carbon $now, Carbon $until): int
    {
        $wait = max(0, $until->getTimestamp() - $now->getTimestamp());

        return max(1, (int) ceil($wait / 60));
    }

    private function cacheKey(int $userId): string
    {
        return "channels.direct.api_throttle.user.{$userId}";
    }

    /**
     * @return array{attempts: int, last_at: ?Carbon, blocked_until: ?Carbon}
     */
    private function readState(string $key): array
    {
        $raw = Cache::get($key);

        if (! is_array($raw)) {
            return $this->emptyState();
        }

        return [
            'attempts' => (int) ($raw['attempts'] ?? 0),
            'last_at' => isset($raw['last_at']) ? Carbon::parse($raw['last_at']) : null,
            'blocked_until' => isset($raw['blocked_until']) ? Carbon::parse($raw['blocked_until']) : null,
        ];
    }

    /**
     * @param  array{attempts: int, last_at: ?Carbon, blocked_until: ?Carbon}  $state
     */
    private function writeState(string $key, array $state): void
    {
        Cache::put($key, [
            'attempts' => $state['attempts'],
            'last_at' => $state['last_at']?->toIso8601String(),
            'blocked_until' => $state['blocked_until']?->toIso8601String(),
        ], self::BLOCK_SECONDS * 2);
    }

    /**
     * @return array{attempts: int, last_at: ?Carbon, blocked_until: ?Carbon}
     */
    private function emptyState(): array
    {
        return [
            'attempts' => 0,
            'last_at' => null,
            'blocked_until' => null,
        ];
    }
}
