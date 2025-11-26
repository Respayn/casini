<?php

namespace Src\Planning\Application\Services;

use Src\Planning\Domain\ProjectPlan;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;

class PlanCalculator
{
    public function recalculate(ProjectPlan $plan, ProjectType $projectType, Kpi $kpi): void
    {
        for ($month = 1; $month <= 12; $month++) {
            $this->recalculateMonth($plan, $projectType, $kpi, $month);
        }
    }

    public function recalculateMonth(ProjectPlan $plan, ProjectType $projectType, Kpi $kpi, int $month): void
    {
        if ($projectType === ProjectType::CONTEXT_AD){
            if ($kpi === Kpi::TRAFFIC) {
                $this->recalculateTrafficValues($plan, $month);
            } elseif ($kpi === Kpi::LEADS) {
                $this->recalculateLeadsValues($plan, $month);
            }
        }
    }

    private function recalculateTrafficValues(ProjectPlan $plan, int $month): void
    {
        $budget = $plan->getMonthlyValue('budget', $month);
        $cpc = $plan->getMonthlyValue('cpc', $month);

        if ($budget !== null && $cpc !== null && $cpc > 0) {
            $visits = round($budget / $cpc);
            $plan->setMonthlyValue('visits', $month, $visits);
        }
    }

    private function recalculateLeadsValues(ProjectPlan $plan, int $month): void
    {
        $budget = $plan->getMonthlyValue('budget', $month);
        $cpl = $plan->getMonthlyValue('cpl', $month);

        if ($budget !== null && $cpl !== null && $cpl > 0) {
            $leads = round($budget / $cpl);
            $plan->setMonthlyValue('leads', $month, $leads);
        }
    }
}