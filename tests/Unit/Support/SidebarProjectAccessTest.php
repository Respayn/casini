<?php

namespace Tests\Unit\Support;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\SidebarProjectAccess;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class SidebarProjectAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_user_with_all_permission_can_access_any_project(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        $this->assertTrue(SidebarProjectAccess::userCanAccessProject($project->id));
    }

    public function test_inaccessible_project_for_self_user(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('seo');
        $role->givePermissionTo('read clients and projects self');
        $viewer->assignRole($role);
        $this->actingAs($viewer);

        $otherProject = $this->createProject();

        $this->assertFalse(SidebarProjectAccess::userCanAccessProject($otherProject->id));
    }

    public function test_self_user_can_access_own_specialist_project(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('seo');
        $role->givePermissionTo('read clients and projects self');
        $viewer->assignRole($role);
        $this->actingAs($viewer);

        $client = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $viewer->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        $this->assertTrue(SidebarProjectAccess::userCanAccessProject($project->id));
    }

    public function test_user_without_clients_permission_cannot_access_project(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        $viewer = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('office_manager');
        $role->syncPermissions(['read channels']);
        $viewer->assignRole($role);
        $this->actingAs($viewer);

        $this->assertFalse(SidebarProjectAccess::userCanAccessProject($project->id));
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
