<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\CallibriLead as EloquentCallibriLead;
use Src\Domain\Leads\CallibriLead;
use Src\Domain\Leads\CallibriLeadRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class EloquentCallibriLeadRepository implements CallibriLeadRepositoryInterface
{
    /**
     * @param int $projectId
     * @param DateTimeRange|null $period
     * @return CallibriLead[]
     */
    public function findByProjectId(int $projectId, ?DateTimeRange $period = null): array
    {
        $query = EloquentCallibriLead::query()
            ->where('project_id', '=', $projectId);

        if ($period !== null) {
            if ($period->start !== null) {
                $query->where('date', '>=', $period->start);
            }

            if ($period->end !== null) {
                $query->where('date', '<=', $period->end);
            }
        }

        return $query->get()
            ->map(fn(EloquentCallibriLead $lead) => $this->mapToEntity($lead))
            ->toArray();
    }

    private function mapToEntity(EloquentCallibriLead $callibriLead): CallibriLead
    {
        return CallibriLead::restore($callibriLead->toArray());
    }
}
