<?php

namespace Tests\Unit\Planning;

use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Src\Planning\Domain\Project;
use Src\Planning\Domain\ValueObjects\PlanValue;
use Tests\TestCase;

class ProjectPrimaryPlanCellTest extends TestCase
{
    public function test_primary_plan_cell_for_positions_kpi_uses_percent_format(): void
    {
        $project = new Project(
            id: 1,
            name: 'example.ru',
            createdAt: new \DateTimeImmutable('2026-01-01'),
            type: ProjectType::SEO_PROMOTION,
            kpi: Kpi::POSITIONS,
            planValues: [
                new PlanValue('top_percent', 2026, 9, 50.0),
            ],
        );

        $this->assertSame(
            ['value' => 50.0, 'format' => 'percent', 'code' => 'top_percent'],
            $project->getPrimaryPlanCell(2026, 9),
        );
    }

    public function test_primary_plan_cell_for_traffic_kpi_has_no_percent_format(): void
    {
        $project = new Project(
            id: 2,
            name: 'traffic.ru',
            createdAt: new \DateTimeImmutable('2026-01-01'),
            type: ProjectType::SEO_PROMOTION,
            kpi: Kpi::TRAFFIC,
            planValues: [
                new PlanValue('visits', 2026, 9, 5130.0),
            ],
        );

        $this->assertSame(
            ['value' => 5130.0, 'format' => null, 'code' => 'visits'],
            $project->getPrimaryPlanCell(2026, 9),
        );
    }
}
