<?php

namespace Tests\Unit\Support;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\SidebarProjectContext;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class SidebarProjectContextTest extends TestCase
{
    use DatabaseTransactions;

    private SidebarProjectContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->context = app(SidebarProjectContext::class);
        $this->context->clear();
    }

    public function test_set_and_get_for_user_with_all_permission(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        $this->assertTrue($this->context->set($project->id));
        $this->assertSame($project->id, $this->context->get());
    }

    public function test_clear_removes_selection(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        $this->context->set($project->id);
        $this->context->clear();

        $this->assertNull($this->context->get());
    }

    public function test_set_rejects_inaccessible_project_for_self_user(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('seo');
        $role->givePermissionTo('read clients and projects self');
        $viewer->assignRole($role);
        $this->actingAs($viewer);

        $otherProject = $this->createProject();

        $this->assertFalse($this->context->set($otherProject->id));
        $this->assertNull($this->context->get());
    }

    public function test_self_user_can_select_own_specialist_project(): void
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

        $this->assertTrue($this->context->set($project->id));
        $this->assertSame($project->id, $this->context->get());
    }

    public function test_get_clears_stale_inaccessible_id(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();
        $this->context->set($project->id);

        $viewer = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('office_manager');
        $role->syncPermissions(['read channels']);
        $viewer->assignRole($role);
        $this->actingAs($viewer);

        $this->assertNull($this->context->get());
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
