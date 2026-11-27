<?php

namespace App\Services\IntegrationSync;

use App\Enums\IntegrationSyncItemStatus;
use App\Models\IntegrationSyncItem;
use App\Repositories\IntegrationSyncRunRepository;

/**
 * Снимок текущего ночного (автоматического) прогона для плашки Каналов / Статистики.
 *
 * @phpstan-type AutomaticSyncState array{
 *     status: string,
 *     pending: list<array{id: int, label: string, group: ?string}>,
 *     all_ids: list<int>,
 *     total: int,
 *     current_index: int,
 *     current_project_id: ?int,
 *     current_project_label: ?string,
 *     current_label: ?string,
 *     step_started_at: ?int,
 *     period_from: null,
 *     period_to: null,
 *     include_vat: bool
 * }
 */
class IntegrationAutomaticSyncStatus
{
    public function __construct(
        private readonly IntegrationSyncRunRepository $runs,
    ) {}

    public function isRunning(): bool
    {
        return $this->runs->hasRunning();
    }

    /**
     * @return AutomaticSyncState|null
     */
    public function currentSnapshot(): ?array
    {
        $run = $this->runs->latestRunning();

        if ($run === null) {
            return null;
        }

        $items = $this->runs->itemsForRunOrdered((int) $run->id);

        if ($items->isEmpty()) {
            return null;
        }

        $allIds = $items
            ->pluck('project_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $finished = $items->filter(
            static fn (IntegrationSyncItem $item): bool => in_array(
                $item->status,
                [IntegrationSyncItemStatus::Done, IntegrationSyncItemStatus::Failed],
                true,
            ),
        )->count();

        $processing = $items->first(
            static fn (IntegrationSyncItem $item): bool => $item->status === IntegrationSyncItemStatus::Processing,
        );

        $pendingByProject = [];
        foreach ($items as $item) {
            if (! in_array(
                $item->status,
                [IntegrationSyncItemStatus::Pending, IntegrationSyncItemStatus::Processing],
                true,
            )) {
                continue;
            }

            $projectId = (int) $item->project_id;
            if ($projectId <= 0 || isset($pendingByProject[$projectId])) {
                continue;
            }

            $pendingByProject[$projectId] = [
                'id' => $projectId,
                'label' => filled($item->project?->name)
                    ? (string) $item->project->name
                    : '№'.$projectId,
                'group' => null,
            ];
        }

        $stepStartedAt = null;
        if ($processing?->updated_at !== null) {
            $stepStartedAt = $processing->updated_at->getTimestamp();
        } elseif ($run->started_at !== null) {
            $stepStartedAt = $run->started_at->getTimestamp();
        }

        return [
            'status' => IntegrationManualRefreshProgress::STATUS_RUNNING,
            'pending' => array_values($pendingByProject),
            'all_ids' => $allIds,
            'total' => $items->count(),
            'current_index' => $processing !== null ? $finished + 1 : $finished,
            'current_project_id' => $processing !== null ? (int) $processing->project_id : null,
            'current_project_label' => $processing !== null
                ? (filled($processing->project?->name)
                    ? (string) $processing->project->name
                    : '№'.$processing->project_id)
                : null,
            'current_label' => $processing !== null
                ? IntegrationManualRefreshProgress::labelForSource((string) $processing->collector)
                : null,
            'step_started_at' => $stepStartedAt,
            'period_from' => null,
            'period_to' => null,
            'include_vat' => false,
        ];
    }
}
