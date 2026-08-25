<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Sidebar;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\SidebarProjectContext;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class SidebarProjectFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        app(SidebarProjectContext::class)->clear();
    }

    public function test_select_project_stores_in_session_and_dispatches_event(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        Livewire::test(Sidebar::class)
            ->call('selectProject', $project->id)
            ->assertSet('selectedProjectId', $project->id)
            ->assertDispatched('sidebar-project-selected', projectId: $project->id);

        $this->assertSame($project->id, app(SidebarProjectContext::class)->get());
    }

    public function test_second_click_on_same_project_clears_filter(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        Livewire::test(Sidebar::class)
            ->call('selectProject', $project->id)
            ->call('selectProject', $project->id)
            ->assertSet('selectedProjectId', null)
            ->assertDispatched('sidebar-project-cleared');

        $this->assertNull(app(SidebarProjectContext::class)->get());
    }

    public function test_reset_selected_project_clears_filter(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        Livewire::test(Sidebar::class)
            ->call('selectProject', $project->id)
            ->call('resetSelectedProject')
            ->assertSet('selectedProjectId', null)
            ->assertDispatched('sidebar-project-cleared');

        $this->assertNull(app(SidebarProjectContext::class)->get());
    }

    public function test_clear_filters_clears_search_and_selected_project(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();

        $component = Livewire::test(Sidebar::class)
            ->call('selectProject', $project->id)
            ->set('searchQuery', 'Sidebar');

        $this->assertTrue($component->instance()->canClearFilters);

        $component
            ->call('clearFilters')
            ->assertSet('selectedProjectId', null)
            ->assertSet('searchQuery', '')
            ->assertDispatched('sidebar-project-cleared');

        $this->assertFalse($component->instance()->canClearFilters);
        $this->assertNull(app(SidebarProjectContext::class)->get());
    }

    public function test_mount_restores_selection_from_session(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');
        $project = $this->createProject();
        app(SidebarProjectContext::class)->set($project->id);

        Livewire::test(Sidebar::class)
            ->assertSet('selectedProjectId', $project->id);
    }

    private function createProject(): Project
    {
        $client = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);

        return Project::factory()->create([
            'name' => 'Sidebar Filter Project',
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
