<?php

namespace App\Repositories;

use App\Models\GoogleSheetsMonthlySpending;
use Illuminate\Support\Collection;

class GoogleSheetsMonthlySpendingRepository
{
    /**
     * @param  Collection<int, int|string>  $projectIds
     * @return Collection<int|string, GoogleSheetsMonthlySpending>
     */
    public function forProjectsAndMonth(Collection $projectIds, string $yearMonth): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return GoogleSheetsMonthlySpending::query()
            ->whereIn('project_id', $projectIds)
            ->whereDate('year_month', $yearMonth)
            ->get()
            ->keyBy('project_id');
    }

    public function firstOrNewForProjectMonth(int $projectId, string $yearMonth): GoogleSheetsMonthlySpending
    {
        return GoogleSheetsMonthlySpending::query()->firstOrNew([
            'project_id' => $projectId,
            'year_month' => $yearMonth,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateOrCreateForProjectMonth(int $projectId, string $yearMonth, array $attributes): GoogleSheetsMonthlySpending
    {
        return GoogleSheetsMonthlySpending::query()->updateOrCreate(
            [
                'project_id' => $projectId,
                'year_month' => $yearMonth,
            ],
            $attributes,
        );
    }
}
