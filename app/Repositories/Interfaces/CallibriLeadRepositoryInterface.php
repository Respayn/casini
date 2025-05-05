<?php

namespace App\Repositories\Interfaces;

interface CallibriLeadRepositoryInterface
{
    public function saveLead(array $leadData, int $projectId): void;
    public function isDuplicate(int $projectId, string $externalId): bool;
}
