<?php

namespace Tests\Feature\Services\Reports;

use App\Models\CallibriDailyLeadCount;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\YandexDirectDailySpending;
use App\Models\YandexSearchApiDailyTopPercent;
use App\Services\Reports\ReportMonthAvailability;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ReportMonthAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_month_is_available_when_last_day_slice_fills_summary(): void
    {
        $project = $this->createProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $this->seedLeadsLastDay($project->id, '2026-07-31');

        $months = app(ReportMonthAvailability::class)->monthsWithSummary($project->id);

        $this->assertSame(['2026-07'], $months);
    }

    public function test_month_without_last_day_slice_is_unavailable(): void
    {
        $project = $this->createProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $this->seedLeadsLastDay($project->id, '2026-07-15');

        $months = app(ReportMonthAvailability::class)->monthsWithSummary($project->id);

        $this->assertSame([], $months);
    }

    public function test_current_unclosed_month_is_unavailable(): void
    {
        $project = $this->createProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $this->seedLeadsLastDay($project->id, '2026-10-15');

        $months = app(ReportMonthAvailability::class)->monthsWithSummary($project->id);

        $this->assertSame([], $months);
    }

    public function test_traffic_kpi_stays_empty_without_visits_in_summary(): void
    {
        $project = $this->createProject(ProjectType::SEO_PROMOTION, Kpi::TRAFFIC);
        YandexSearchApiDailyTopPercent::query()->create([
            'project_id' => $project->id,
            'date' => '2026-09-30',
            'top_10_percent' => 40.0,
            'phrases_count' => 10,
        ]);

        $months = app(ReportMonthAvailability::class)->monthsWithSummary($project->id);

        $this->assertSame([], $months);
    }

    public function test_positions_kpi_uses_search_api_last_day(): void
    {
        $project = $this->createProject(ProjectType::SEO_PROMOTION, Kpi::POSITIONS);
        YandexSearchApiDailyTopPercent::query()->create([
            'project_id' => $project->id,
            'date' => '2026-09-30',
            'top_10_percent' => 40.0,
            'phrases_count' => 10,
        ]);

        $months = app(ReportMonthAvailability::class)->monthsWithSummary($project->id);

        $this->assertSame(['2026-09'], $months);
    }

    private function createProject(ProjectType $type, Kpi $kpi): Project
    {
        $client = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);

        return Project::factory()->create([
            'name' => 'Availability '.$type->value,
            'client_id' => $client->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => $type,
            'kpi' => $kpi,
        ]);
    }

    private function seedLeadsLastDay(int $projectId, string $date): void
    {
        YandexDirectDailySpending::query()->create([
            'project_id' => $projectId,
            'date' => $date,
            'cost_with_vat' => 100,
            'cost_without_vat' => 80,
        ]);
        CallibriDailyLeadCount::query()->create([
            'project_id' => $projectId,
            'date' => $date,
            'leads_count' => 4,
        ]);
    }
}
