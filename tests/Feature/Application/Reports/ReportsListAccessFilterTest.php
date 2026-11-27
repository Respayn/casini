<?php

namespace Tests\Feature\Application\Reports;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
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

class ReportsListAccessFilterTest extends TestCase
{
    use DatabaseTransactions;

    private int $templateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->templateId = DB::table('templates')->insertGetId([
            'name' => 'Шаблон для теста доступа',
            'path' => 'templates/access.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_self_user_sees_own_project_report_and_not_foreign(): void
    {
        $viewer = $this->userWithPermissions([
            'read clients and projects self',
        ]);

        $ownProject = $this->createProject(specialist: $viewer);
        $foreignProject = $this->createProject();

        $ownReport = $this->createReport($ownProject, $viewer);
        $foreignReport = $this->createReport($foreignProject, $viewer);

        $ids = $this->reportIds($this->getList($viewer->id));

        $this->assertContains($ownReport, $ids);
        $this->assertNotContains($foreignReport, $ids);
    }

    public function test_all_permission_sees_foreign_report(): void
    {
        $viewer = $this->userWithPermissions([
            'read clients and projects all',
        ]);

        $foreignProject = $this->createProject();
        $foreignReport = $this->createReport($foreignProject, $viewer);

        $ids = $this->reportIds($this->getList($viewer->id));

        $this->assertContains($foreignReport, $ids);
    }

    public function test_seo_head_without_all_permission_does_not_see_all_seo_reports(): void
    {
        $viewer = $this->userWithPermissions(
            ['read clients and projects self'],
            'rucovotdelseo',
        );

        $foreignSeoProject = $this->createProject();
        $foreignReport = $this->createReport($foreignSeoProject, $viewer);

        $ids = $this->reportIds($this->getList($viewer->id));

        $this->assertNotContains($foreignReport, $ids);
    }

    /**
     * @return array<ReportListItemDto>
     */
    private function getList(int $userId): array
    {
        return app(ReportsListDataProvider::class)->getList(
            false,
            new DateTimeRange(
                new DateTimeImmutable('2026-09-01 00:00:00'),
                new DateTimeImmutable('2026-09-30 23:59:59'),
            ),
            $userId,
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

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions, string $roleName = 'seo'): User
    {
        $role = Role::findByName($roleName);
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function createProject(?User $specialist = null): Project
    {
        $client = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);

        return Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => ($specialist ?? User::factory()->create(['is_active' => true]))->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);
    }

    private function createReport(Project $project, User $createdBy): int
    {
        return DB::table('reports')->insertGetId([
            'template_id' => $this->templateId,
            'template_name' => 'Шаблон для теста доступа',
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'specialist_id' => $project->specialist_id,
            'manager_id' => $createdBy->id,
            'format' => 'docx',
            'created_by' => $createdBy->id,
            'path' => 'reports/access.docx',
            'created_at' => '2026-09-10 12:00:00',
            'updated_at' => '2026-09-10 12:00:00',
        ]);
    }
}
