<?php

namespace App\Repositories;

use App\Models\CallibriLead;
use App\Repositories\Interfaces\CallibriLeadRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CallibriLeadRepository implements CallibriLeadRepositoryInterface
{
    public function saveLead(array $leadData, int $projectId): void
    {
        try {
            CallibriLead::updateOrCreate(
                [
                    'project_id' => $projectId,
                    'external_id' => $leadData['id'],
                ],
                [
                    'date' => Carbon::parse($leadData['date']),
                    'utm_source' => $leadData['utm_source'] ?? null,
                    'utm_campaign' => $leadData['utm_campaign'] ?? null,
                    'utm_medium' => $leadData['utm_medium'] ?? null,
                    'utm_content' => $leadData['utm_content'] ?? null,
                    'utm_term' => $leadData['utm_term'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to save Callibri lead', [
                'project_id' => $projectId,
                'lead' => $leadData,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function isDuplicate(int $projectId, string $externalId): bool
    {
        return CallibriLead::where([
            'project_id' => $projectId,
            'external_id' => $externalId,
        ])->exists();
    }

    public function getLeadsBetween(int $projectId, string $dateTimeFrom, string $dateTimeTo): Collection
    {
        return $this->getLeadsBetweenForProjects([$projectId], $dateTimeFrom, $dateTimeTo);
    }

    public function getLeadsBetweenForProjects(array $projectIds, string $dateTimeFrom, string $dateTimeTo): Collection
    {
        $projectIds = array_values(array_unique(array_map('intval', $projectIds)));
        if ($projectIds === []) {
            return collect();
        }

        return CallibriLead::query()
            ->whereIn('project_id', $projectIds)
            ->whereBetween('date', [$dateTimeFrom, $dateTimeTo])
            ->get();
    }
}
