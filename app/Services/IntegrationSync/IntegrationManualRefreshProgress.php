<?php

namespace App\Services\IntegrationSync;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Прогресс ручного обновления Каналов / Статистики в фоне.
 *
 * @phpstan-type PendingProject array{id: int, label: string, group: ?string}
 * @phpstan-type ProgressState array{
 *     status: string,
 *     run_id: string,
 *     product: string,
 *     pending: list<PendingProject>,
 *     total: int,
 *     current_index: int,
 *     current_project_id: ?int,
 *     current_label: ?string,
 *     step_started_at: ?int,
 *     all_ids: list<int>,
 *     updated: int,
 *     failed: int,
 *     skipped: int,
 *     noticed: bool
 * }
 */
class IntegrationManualRefreshProgress
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const TTL_SECONDS = 21600;

    public const TYPE_REFRESHED = 'report.data.refreshed';

    /**
     * @return array<string, string>
     */
    public static function sourceLabels(): array
    {
        return [
            'yandex_direct_daily_spend' => 'Директ: расход',
            'yandex_direct_campaign_stats' => 'Директ: кампании',
            'callibri_daily_leads' => 'Callibri: лиды',
            'yandex_search_api_daily_positions' => 'Поиск: позиции',
            'bitrix24_labor' => 'Битрикс: часы',
            'bitrix24_works' => 'Битрикс: работы',
            'yandex_metrika' => 'Метрика',
            'google_sheets' => 'Таблицы',
            'direct_budget' => 'Директ: остаток',
            'bonuses' => 'Бонусы',
        ];
    }

    public static function labelForSource(string $key): string
    {
        return self::sourceLabels()[$key] ?? $key;
    }

    public static function productTitle(string $product): string
    {
        return match ($product) {
            'statistics' => 'Статистике',
            default => 'Каналах',
        };
    }

    /**
     * @param  list<PendingProject>  $pending
     * @return ProgressState
     */
    public function start(int $userId, string $product, array $pending): array
    {
        $state = [
            'status' => self::STATUS_RUNNING,
            'run_id' => (string) Str::uuid(),
            'product' => $product,
            'pending' => array_values($pending),
            'all_ids' => array_values(array_map(
                static fn (array $item): int => (int) $item['id'],
                $pending,
            )),
            'total' => count($pending),
            'current_index' => 0,
            'current_project_id' => null,
            'current_label' => null,
            'step_started_at' => null,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'noticed' => false,
        ];

        $this->put($userId, $product, $state);

        return $state;
    }

    /**
     * @return ProgressState|null
     */
    public function get(int $userId, string $product): ?array
    {
        $raw = Cache::get($this->cacheKey($userId, $product));

        return is_array($raw) ? $raw : null;
    }

    public function setCurrentStep(
        int $userId,
        string $product,
        int $projectId,
        string $sourceKey,
        int $currentIndex,
    ): void {
        $this->mutate($userId, $product, function (array $state) use ($projectId, $sourceKey, $currentIndex): array {
            $state['current_project_id'] = $projectId;
            $state['current_label'] = self::labelForSource($sourceKey);
            $state['current_index'] = $currentIndex;
            $state['step_started_at'] = time();

            return $state;
        });
    }

    public function markProjectFinished(
        int $userId,
        string $product,
        int $projectId,
        string $outcome,
    ): ?array {
        return $this->mutate($userId, $product, function (array $state) use ($projectId, $outcome): array {
            $state['pending'] = array_values(array_filter(
                $state['pending'] ?? [],
                static fn (array $item): bool => (int) ($item['id'] ?? 0) !== $projectId,
            ));

            if ($outcome === 'updated') {
                $state['updated'] = (int) ($state['updated'] ?? 0) + 1;
            } elseif ($outcome === 'failed') {
                $state['failed'] = (int) ($state['failed'] ?? 0) + 1;
            } else {
                $state['skipped'] = (int) ($state['skipped'] ?? 0) + 1;
            }

            $state['current_project_id'] = null;
            $state['current_label'] = null;
            $state['step_started_at'] = null;

            return $state;
        });
    }

    public function markDone(int $userId, string $product): ?array
    {
        return $this->mutate($userId, $product, function (array $state): array {
            $state['status'] = self::STATUS_DONE;
            $state['pending'] = [];
            $state['current_project_id'] = null;
            $state['current_label'] = null;
            $state['step_started_at'] = null;

            return $state;
        });
    }

    public function markNoticed(int $userId, string $product): void
    {
        $this->mutate($userId, $product, function (array $state): array {
            $state['noticed'] = true;

            return $state;
        });
    }

    public function clear(int $userId, string $product): void
    {
        Cache::forget($this->cacheKey($userId, $product));
    }

    public function pendingIds(int $userId, string $product): array
    {
        $state = $this->get($userId, $product);

        if ($state === null || ($state['status'] ?? null) !== self::STATUS_RUNNING) {
            return [];
        }

        return array_values(array_map(
            static fn (array $item): int => (int) $item['id'],
            $state['pending'] ?? [],
        ));
    }

    /**
     * @param  callable(ProgressState): ProgressState  $callback
     * @return ProgressState|null
     */
    private function mutate(int $userId, string $product, callable $callback): ?array
    {
        $key = $this->cacheKey($userId, $product);
        $lock = Cache::lock($key.':lock', 10);

        return $lock->block(5, function () use ($userId, $product, $callback) {
            $state = $this->get($userId, $product);

            if ($state === null) {
                return null;
            }

            $next = $callback($state);
            $this->put($userId, $product, $next);

            return $next;
        });
    }

    /**
     * @param  ProgressState  $state
     */
    private function put(int $userId, string $product, array $state): void
    {
        Cache::put($this->cacheKey($userId, $product), $state, self::TTL_SECONDS);
    }

    private function cacheKey(int $userId, string $product): string
    {
        return "integrations.manual_refresh.{$userId}.{$product}";
    }
}
