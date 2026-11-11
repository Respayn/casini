<?php

namespace App\Repositories\Interfaces;

use App\Models\CallibriLead;
use Illuminate\Support\Collection;

interface CallibriLeadRepositoryInterface
{
    public function saveLead(array $leadData, int $projectId): void;

    public function isDuplicate(int $projectId, string $externalId): bool;

    /**
     * @return Collection<int, CallibriLead>
     */
    public function getLeadsBetween(int $projectId, string $dateTimeFrom, string $dateTimeTo): Collection;
}
