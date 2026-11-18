<?php

namespace App\Repositories;

use App\Enums\IntegrationSyncRunStatus;
use App\Models\IntegrationSyncItem;
use App\Models\IntegrationSyncRun;
use Illuminate\Support\Collection;

class IntegrationSyncRunRepository
{
    public function hasRunning(): bool
    {
        return IntegrationSyncRun::query()
            ->where('status', IntegrationSyncRunStatus::Running)
            ->exists();
    }

    public function latestRunning(): ?IntegrationSyncRun
    {
        return IntegrationSyncRun::query()
            ->where('status', IntegrationSyncRunStatus::Running)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, IntegrationSyncItem>
     */
    public function itemsForRunOrdered(int $runId): Collection
    {
        return IntegrationSyncItem::query()
            ->where('run_id', $runId)
            ->with(['project:id,name'])
            ->orderBy('position')
            ->get();
    }
}
