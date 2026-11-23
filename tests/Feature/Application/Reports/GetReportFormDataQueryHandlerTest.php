<?php

namespace Tests\Feature\Application\Reports;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Src\Application\Reports\GetFormData\GetReportFormDataQuery;
use Src\Application\Reports\GetFormData\GetReportFormDataQueryHandler;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class GetReportFormDataQueryHandlerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_self_user_sees_only_own_projects_without_n_plus_one(): void
    {
        $viewer = $this->userWithPermissions(['read clients and projects self']);

        $ownProject = $this->createProject(specialist: $viewer, name: 'Свой проект');
        $this->createProject(name: 'Чужой проект A');
        $this->createProject(name: 'Чужой проект B');
        $this->createProject(name: 'Чужой проект C');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = app(GetReportFormDataQueryHandler::class)->handle(
            new GetReportFormDataQuery($viewer->id)
        );

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $projectIds = array_column($response->projects, 'value');

        $this->assertSame([$ownProject->id], $projectIds);
        $this->assertLessThanOrEqual(5, count($queries));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::findByName('seo');
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function createProject(?User $specialist = null, string $name = 'Проект'): Project
    {
        $client = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);

        return Project::factory()->create([
            'name' => $name,
            'client_id' => $client->id,
            'specialist_id' => ($specialist ?? User::factory()->create(['is_active' => true]))->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);
    }
}
