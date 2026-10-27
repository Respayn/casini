<?php

namespace Tests\Feature\Application\Reports;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Src\Application\Reports\GetList\ReportListItemDto;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Src\Infrastructure\Queries\ReportsListDataProvider;
use Tests\TestCase;

class ReportsListPeriodFilterTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private int $templateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(Role::findByName('admin'));

        $this->templateId = DB::table('templates')->insertGetId([
            'name' => 'Шаблон для теста списка',
            'path' => 'templates/test.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_last_day_of_month_is_included_until_end_of_day(): void
    {
        $project = $this->createProject();
        $evening = $this->createReport($project, '2026-09-30 20:15:00');
        $nextMonth = $this->createReport($project, '2026-10-01 00:00:01');
        $previousMonth = $this->createReport($project, '2026-08-31 23:59:00');

        $ids = $this->reportIds($this->getList('2026-09-01 00:00:00', '2026-09-30 23:59:59'));

        $this->assertContains($evening, $ids);
        $this->assertNotContains($nextMonth, $ids);
        $this->assertNotContains($previousMonth, $ids);
    }

    public function test_sidebar_project_keeps_only_its_reports(): void
    {
        $selected = $this->createProject();
        $other = $this->createProject();
        $selectedReport = $this->createReport($selected, '2026-09-10 12:00:00');
        $otherReport = $this->createReport($other, '2026-09-10 12:00:00');

        $ids = $this->reportIds($this->getList('2026-09-01 00:00:00', '2026-09-30 23:59:59', $selected->id));

        $this->assertSame([$selectedReport], $ids);
        $this->assertNotContains($otherReport, $ids);
    }

    public function test_list_keeps_template_name_after_template_deleted(): void
    {
        $project = $this->createProject();
        $reportId = $this->createReport($project, '2026-09-10 12:00:00');

        DB::table('templates')->where('id', $this->templateId)->delete();

        $list = $this->getList('2026-09-01 00:00:00', '2026-09-30 23:59:59');
        $item = collect($list)->first(
            fn (ReportListItemDto $report): bool => (int) $report->reportId === $reportId
        );

        $this->assertNotNull($item);
        $this->assertSame('Шаблон для теста списка', $item->templateName);
        $this->assertDatabaseHas('reports', [
            'id' => $reportId,
            'template_id' => null,
            'template_name' => 'Шаблон для теста списка',
        ]);
    }

    /**
     * @return array<ReportListItemDto>
     */
    private function getList(string $start, string $end, ?int $projectId = null): array
    {
        return app(ReportsListDataProvider::class)->getList(
            false,
            new DateTimeRange(new DateTimeImmutable($start), new DateTimeImmutable($end)),
            $this->admin->id,
            $projectId,
        );
    }

    /**
     * @param  array<ReportListItemDto>  $reports
     * @return array<int>
     */
    private function reportIds(array $reports): array
    {
        return array_map(fn (ReportListItemDto $report) => (int) $report->reportId, $reports);
    }

    private function createProject(): Project
    {
        $client = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);

        return Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);
    }

    private function createReport(Project $project, string $createdAt): int
    {
        return DB::table('reports')->insertGetId([
            'template_id' => $this->templateId,
            'template_name' => 'Шаблон для теста списка',
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'specialist_id' => $project->specialist_id,
            'manager_id' => $this->admin->id,
            'format' => 'docx',
            'created_by' => $this->admin->id,
            'path' => 'reports/test.docx',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
