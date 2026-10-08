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

class ClientProjectFormPermissionsTest extends TestCase
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

    private function projectForUser(User $user): Project
    {
        $client = Client::factory()->create(['manager_id' => $user->id]);

        return Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);
    }

    public function test_add_assistant_forbidden_without_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);
        $project = $this->projectForUser($user);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('addAssistant')
            ->assertForbidden();
    }

    public function test_save_forbidden_without_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);
        $project = $this->projectForUser($user);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('save')
            ->assertForbidden();
    }

    public function test_add_assistant_appends_row_with_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
            'edit clients and projects all',
        ]);
        $project = $this->projectForUser($user);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('addAssistant')
            ->assertSet('clientProjectForm.assistants.1', null);
    }
}
