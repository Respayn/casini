<?php

namespace App\Services\IntegrationSync;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Прогресс ручного обновления Каналов / Статистики в фоне (один на агентство).
 *
 * @phpstan-type PendingProject array{id: int, label: string, group: ?string}
 * @phpstan-type ProgressState array{
 *     status: string,
 *     run_id: string,
 *     agency_id: int,
 *     product: string,
 *     pending: list<PendingProject>,
 *     total: int,
 *     current_index: int,
 *     current_project_id: ?int,
 *     current_project_label: ?string,
 *     current_label: ?string,
 *     step_started_at: ?int,
 *     period_from: string,
 *     period_to: string,
 *     include_vat: bool,
 *     all_ids: list<int>,
 *     updated: int,
 *     failed: int,
 *     skipped: int,
 *     noticed: list<string>
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

    public static function sharedFinishMessage(int $failed = 0): string
    {
        $message = 'Данные в Каналах и Статистике обновлены';

        if ($failed > 0) {
            $message .= ". Ошибок: {$failed}";
        }

        return $message;
    }

    public static function noticeKey(int $userId, string $product): string
    {
        return $userId.':'.$product;
    }

    /**
     * @param  list<PendingProject>  $pending
     * @return ProgressState
     */
    public function start(
        int $agencyId,
        string $product,
        array $pending,
        string $periodFrom,
        string $periodTo,
        bool $includeVat,
    ): array {
        $state = [
            'status' => self::STATUS_RUNNING,
            'run_id' => (string) Str::uuid(),
            'agency_id' => $agencyId,
            'product' => $product,
            'pending' => array_values($pending),
            'all_ids' => array_values(array_map(
                static fn (array $item): int => (int) $item['id'],
                $pending,
            )),
            'total' => count($pending),
            'current_index' => 0,
            'current_project_id' => null,
            'current_project_label' => null,
            'current_label' => null,
            'step_started_at' => null,
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'include_vat' => $includeVat,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'noticed' => [],
        ];

        $this->put($agencyId, $state);

        return $state;
    }

    /**
     * Строка ещё в очереди и на экране тот же период / НДС, что при клике.
     *
     * @param  ProgressState|array<string, mixed>  $state
     */
    public static function isProjectPendingInSlice(
        array $state,
        int $projectId,
        string $periodFrom,
        string $periodTo,
        bool $includeVat,
    ): bool {
        if (($state['status'] ?? null) !== self::STATUS_RUNNING) {
            return false;
        }

        if (($state['period_from'] ?? null) !== $periodFrom
            || ($state['period_to'] ?? null) !== $periodTo
            || (bool) ($state['include_vat'] ?? false) !== $includeVat
        ) {
            return false;
        }

        foreach ($state['pending'] ?? [] as $item) {
            if ((int) ($item['id'] ?? 0) === $projectId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ProgressState|null
     */
    public function get(int $agencyId): ?array
    {
        $raw = Cache::get($this->cacheKey($agencyId));

        return is_array($raw) ? $raw : null;
    }

    public function isRunning(int $agencyId): bool
    {
        $state = $this->get($agencyId);

        return ($state['status'] ?? null) === self::STATUS_RUNNING;
    }

    public function setCurrentStep(
        int $agencyId,
        int $projectId,
        string $sourceKey,
        int $currentIndex,
    ): void {
        $this->mutate($agencyId, function (array $state) use ($projectId, $sourceKey, $currentIndex): array {
            $state['current_project_id'] = $projectId;
            $state['current_label'] = self::labelForSource($sourceKey);
            $state['current_index'] = $currentIndex;
            $state['step_started_at'] = time();
            $state['current_project_label'] = $sourceKey === 'bonuses'
                ? null
                : $this->labelForProject($state, $projectId);

            return $state;
        });
    }

    public function markProjectFinished(
        int $agencyId,
        int $projectId,
        string $outcome,
    ): ?array {
        return $this->mutate($agencyId, function (array $state) use ($projectId, $outcome): array {
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
            $state['current_project_label'] = null;
            $state['current_label'] = null;
            $state['step_started_at'] = null;

            return $state;
        });
    }

    public function markDone(int $agencyId): ?array
    {
        return $this->mutate($agencyId, function (array $state): array {
            $state['status'] = self::STATUS_DONE;
            $state['pending'] = [];
            $state['current_project_id'] = null;
            $state['current_project_label'] = null;
            $state['current_label'] = null;
            $state['step_started_at'] = null;

            return $state;
        });
    }

    public function markNoticed(int $agencyId, int $userId, string $product): void
    {
        $noticeKey = self::noticeKey($userId, $product);

        $this->mutate($agencyId, function (array $state) use ($noticeKey): array {
            $noticed = array_values(array_map(
                static fn ($item): string => (string) $item,
                $state['noticed'] ?? [],
            ));

            if (! in_array($noticeKey, $noticed, true)) {
                $noticed[] = $noticeKey;
            }

            $state['noticed'] = $noticed;

            return $state;
        });
    }

    public function wasNoticed(array $state, int $userId, string $product): bool
    {
        $noticeKey = self::noticeKey($userId, $product);
        $noticed = $state['noticed'] ?? [];

        if (is_bool($noticed)) {
            return $noticed;
        }

        if (! is_array($noticed)) {
            return false;
        }

        return in_array($noticeKey, array_map('strval', $noticed), true);
    }

    public function clear(int $agencyId): void
    {
        Cache::forget($this->cacheKey($agencyId));
    }

    /**
     * @return list<int>
     */
    public function pendingIds(int $agencyId): array
    {
        $state = $this->get($agencyId);

        if ($state === null || ($state['status'] ?? null) !== self::STATUS_RUNNING) {
            return [];
        }

        return array_values(array_map(
            static fn (array $item): int => (int) $item['id'],
            $state['pending'] ?? [],
        ));
    }

    /**
     * @param  ProgressState|array<string, mixed>  $state
     */
    private function labelForProject(array $state, int $projectId): ?string
    {
        foreach ($state['pending'] ?? [] as $item) {
            if ((int) ($item['id'] ?? 0) === $projectId) {
                $label = (string) ($item['label'] ?? '');

                return $label !== '' ? $label : null;
            }
        }

        return null;
    }

    /**
     * @param  callable(ProgressState): ProgressState  $callback
     * @return ProgressState|null
     */
    private function mutate(int $agencyId, callable $callback): ?array
    {
        $key = $this->cacheKey($agencyId);
        $lock = Cache::lock($key.':lock', 10);

        return $lock->block(5, function () use ($agencyId, $callback) {
            $state = $this->get($agencyId);

            if ($state === null) {
                return null;
            }

            $next = $callback($state);
            $this->put($agencyId, $next);

            return $next;
        });
    }

    /**
     * @param  ProgressState  $state
     */
    private function put(int $agencyId, array $state): void
    {
        Cache::put($this->cacheKey($agencyId), $state, self::TTL_SECONDS);
    }

    private function cacheKey(int $agencyId): string
    {
        return "integrations.manual_refresh.{$agencyId}";
    }
}
