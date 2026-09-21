<?php

namespace Tests\Unit\Planning;

use DateTimeImmutable;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Src\Domain\ValueObjects\Quarter;
use Src\Planning\Application\Services\KpiParametersSchemaService;
use Src\Planning\Application\Services\PlanCalculator;
use Src\Planning\Domain\Project;
use Src\Planning\Domain\ProjectPlan;
use Src\Planning\Domain\ValueObjects\QuarterApproval;
use Tests\TestCase;

class PlanningTableEnhancementsTest extends TestCase
{
    public function test_quarter_approval_stores_date_and_user(): void
    {
        $approval = new QuarterApproval(new Quarter(1), true, '2026-08-10', 42);

        $this->assertTrue($approval->isApproved());
        $this->assertSame('2026-08-10', $approval->getApprovedAt());
        $this->assertSame(42, $approval->getApprovedBy());
    }

    public function test_quarter_approval_clears_meta_when_not_approved(): void
    {
        $approval = new QuarterApproval(new Quarter(2), false, '2026-08-10', 42);

        $this->assertFalse($approval->isApproved());
        $this->assertNull($approval->getApprovedAt());
        $this->assertNull($approval->getApprovedBy());
    }

    public function test_seo_traffic_schema_includes_conversions_and_primary_visits(): void
    {
        $schema = (new KpiParametersSchemaService)->createSchema(
            ProjectType::SEO_PROMOTION,
            Kpi::TRAFFIC,
        );

        $byId = [];
        foreach ($schema->getParameters() as $parameter) {
            $byId[$parameter->getId()] = $parameter;
        }

        $this->assertArrayHasKey('visits', $byId);
        $this->assertArrayHasKey('conversions', $byId);
        $this->assertSame('Конверсии', $byId['conversions']->getLabel());
        $this->assertTrue($byId['visits']->isPrimary());
        $this->assertTrue($byId['visits']->shouldRoundToInteger());
        $this->assertSame('integer', $byId['conversions']->getFormat());
    }

    public function test_seo_positions_schema_uses_integer_percent_label(): void
    {
        $schema = (new KpiParametersSchemaService)->createSchema(
            ProjectType::SEO_PROMOTION,
            Kpi::POSITIONS,
        );

        $byId = [];
        foreach ($schema->getParameters() as $parameter) {
            $byId[$parameter->getId()] = $parameter;
        }

        $this->assertSame('% позиций в ТОП 10', $byId['top_percent']->getLabel());
        $this->assertTrue($byId['top_percent']->isPrimary());
        $this->assertTrue($byId['top_percent']->shouldRoundToInteger());
        $this->assertArrayHasKey('conversions', $byId);
    }

    public function test_plan_calculator_rounds_visits_to_integer(): void
    {
        $project = new Project(
            1,
            'Test',
            new DateTimeImmutable('2026-01-01'),
            ProjectType::CONTEXT_AD,
            Kpi::TRAFFIC,
        );
        $plan = new ProjectPlan($project, 2026);
        $plan->setMonthlyValue('budget', 1, 1000);
        $plan->setMonthlyValue('cpc', 1, 3);

        (new PlanCalculator)->recalculateMonth($plan, ProjectType::CONTEXT_AD, Kpi::TRAFFIC, 1);

        $this->assertSame(333.0, $plan->getMonthlyValue('visits', 1));
    }

    public function test_project_plan_value_rounds_calculated_visits(): void
    {
        $project = new Project(
            1,
            'Test',
            new DateTimeImmutable('2026-01-01'),
            ProjectType::CONTEXT_AD,
            Kpi::TRAFFIC,
        );
        $project->setPlanValue('budget', 2026, 1, 1000);
        $project->setPlanValue('cpc', 2026, 1, 3);

        $this->assertSame(333.0, $project->getPlanValue('visits', 2026, 1));
        $this->assertSame(333.0, $project->getPrimaryPlanValue(2026, 1));
    }
}
