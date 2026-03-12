<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\ProjectPlanValue as EloquentProjectPlanValue;
use Src\Domain\Projects\ProjectPlanValue;
use Src\Domain\Projects\ProjectPlanValueRepositoryInterface;
use Src\Domain\ValueObjects\DateTimeRange;

class EloquentProjectPlanValueRepository implements ProjectPlanValueRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findByCodeAndMonth(int $projectId, string $parameterCode, DateTimeRange $period): ?ProjectPlanValue
    {
        $query = EloquentProjectPlanValue::query()
            ->where('project_id', '=', $projectId)
            ->where('parameter_code', '=', $parameterCode);

        if ($period->start !== null) {
            $query->where('year_month_date', '>=', $period->start->format('Y-m-01'));
        }

        if ($period->end !== null) {
            $query->where('year_month_date', '<=', $period->end->format('Y-m-01'));
        }

        $model = $query->first();

        if ($model === null) {
            return null;
        }

        return ProjectPlanValue::restore($model->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function findByCodes(int $projectId, array $parameterCodes, DateTimeRange $period): array
    {
        $query = EloquentProjectPlanValue::query()
            ->where('project_id', '=', $projectId)
            ->whereIn('parameter_code', $parameterCodes);

        if ($period->start !== null) {
            $query->where('year_month_date', '>=', $period->start->format('Y-m-01'));
        }

        if ($period->end !== null) {
            $query->where('year_month_date', '<=', $period->end->format('Y-m-01'));
        }

        $models = $query->get();

        $result = [];
        foreach ($parameterCodes as $code) {
            $result[$code] = null;
        }

        foreach ($models as $model) {
            $result[$model->parameter_code] = ProjectPlanValue::restore($model->toArray());
        }

        return $result;
    }
}
