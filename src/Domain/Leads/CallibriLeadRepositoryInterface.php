<?php

namespace Src\Domain\Leads;

use Src\Domain\ValueObjects\DateTimeRange;

interface CallibriLeadRepositoryInterface
{
    /**
     * @param int $projectId
     * @param DateTimeRange|null $period
     * @return CallibriLead[]
     */
    public function findByProjectId(int $projectId, ?DateTimeRange $period = null): array;
}
