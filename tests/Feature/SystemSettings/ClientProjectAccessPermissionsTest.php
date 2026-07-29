<?php

namespace Tests\Feature\SystemSettings;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ClientProjectAccessPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function actingManagerWithPermissions(array $permissions): User
    {
        $role = Role::findByName('manager');
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        return $user;
    }

    public function test_product_forbidden_without_any_clients_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings roles and permissions',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_parent_read_opens_list(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects'))
            ->assertOk();
    }

    public function test_read_only_list_does_not_dispatch_client_edit(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $client = Client::factory()->create([
            'name' => 'Readonly Client Name',
            'manager_id' => $manager->id,
        ]);

        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects'))
            ->assertOk()
            ->assertSee('Readonly Client Name', false)
            ->assertDontSee("\$dispatch('client-edit'", false)
            ->assertDontSee('wire:click="$dispatch(\'client-edit\'', false);
    }

    public function test_edit_list_allows_client_edit_dispatch(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        Client::factory()->create([
            'name' => 'Editable Client Name',
            'manager_id' => $manager->id,
        ]);

        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
            'edit clients and projects all',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects'))
            ->assertOk()
            ->assertSee("\$dispatch('client-edit'", false);
    }

    public function test_foreign_project_forbidden_for_self_user(): void
    {
        $otherManager = User::factory()->create(['is_active' => true]);
        $otherSpecialist = User::factory()->create(['is_active' => true]);

        $client = Client::factory()->create(['manager_id' => $otherManager->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $otherSpecialist->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);

        $user = $this->actingManagerWithPermissions([
            'read clients and projects self',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects.projects.manage', $project->id))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_own_project_as_specialist_is_allowed_for_self(): void
    {
        $otherManager = User::factory()->create(['is_active' => true]);

        $user = $this->actingManagerWithPermissions([
            'read clients and projects self',
        ]);

        $client = Client::factory()->create(['manager_id' => $otherManager->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects.projects.manage', $project->id));

        $this->assertNotSame(403, $response->status());
    }

    public function test_save_client_without_edit_is_forbidden(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);

        $client = Client::factory()->create([
            'manager_id' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test('client.create-modal')
            ->set('id', $client->id)
            ->set('name', 'Hacked Name')
            ->set('inn', $client->inn)
            ->set('managerId', $client->manager_id)
            ->set('initialBalance', 100)
            ->call('saveClient')
            ->assertForbidden();
    }

    public function test_project_form_read_only_shows_disabled_submit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);

        $client = Client::factory()->create(['manager_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects.projects.manage', $project->id))
            ->assertOk()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_project_form_save_without_edit_is_forbidden(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);

        $client = Client::factory()->create(['manager_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('save')
            ->assertForbidden();
    }

    public function test_project_form_add_region_without_edit_is_forbidden(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);

        $client = Client::factory()->create(['manager_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('addRegion')
            ->assertForbidden();
    }

    public function test_project_form_with_edit_permission_allows_save_attempt(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
            'edit clients and projects all',
        ]);

        $client = Client::factory()->create(['manager_id' => $user->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);

        $this->actingAs($user);

        $result = Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('addRegion');

        $this->assertNotSame(403, $result->status());
    }
}
