<?php

namespace Tests\Unit\Application\Clients;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Src\Application\Clients\GetClientsWithProjects\GetClientsWithProjectsQuery;
use Src\Application\Clients\GetClientsWithProjects\GetClientsWithProjectsQueryHandler;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class GetClientsWithProjectsSidebarFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_project_id_returns_only_matching_client_and_project(): void
    {
        $viewer = $this->actingAsUserWithPermission('read clients and projects all');

        $clientA = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);
        $clientB = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);

        $projectA = Project::factory()->create([
            'client_id' => $clientA->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        Project::factory()->create([
            'client_id' => $clientB->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        $result = app(GetClientsWithProjectsQueryHandler::class)->handle(
            new GetClientsWithProjectsQuery($viewer->id, $projectA->id)
        );

        $this->assertCount(1, $result);
        $this->assertSame($clientA->id, $result[0]->id);
        $this->assertCount(1, $result[0]->projects);
        $this->assertSame($projectA->id, $result[0]->projects[0]->id);
    }

    private function actingAsUserWithPermission(string $permission): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('admin');
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }
}
