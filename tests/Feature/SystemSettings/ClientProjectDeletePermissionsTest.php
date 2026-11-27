<?php

namespace Tests\Feature\SystemSettings;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ClientProjectDeletePermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_delete_button_hidden_without_full_permission(): void
    {
        $actor = $this->userWithPermissions([
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $project = $this->createProject($actor);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->assertSet('canDeleteClientProject', false)
            ->assertDontSee('Удалить клиенто-проект');
    }

    public function test_delete_button_visible_with_full_permission(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $project = $this->createProject($actor);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->assertSet('canDeleteClientProject', true)
            ->assertSee('Удалить клиенто-проект');
    }

    public function test_create_form_hides_delete_button(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.client-project-form')
            ->assertSet('canDeleteClientProject', false)
            ->assertDontSee('Удалить клиенто-проект');
    }

    public function test_delete_blocked_when_project_has_reports(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $project = $this->createProject($actor);
        $this->insertReport($actor, $project);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('deleteClientProject')
            ->assertSet(
                'deleteError',
                'Нельзя удалить клиенто-проект: на него ссылаются отчеты.'
            );

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_delete_success_removes_project_and_redirects(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $project = $this->createProject($actor);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->call('deleteClientProject')
            ->assertSet('deleteError', null)
            ->assertRedirect(route('system-settings.clients-and-projects'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    private function createProject(User $user): Project
    {
        $client = Client::factory()->create([
            'manager_id' => $user->id,
        ]);

        return Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
        ]);
    }

    private function insertReport(User $user, Project $project): int
    {
        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Шаблон для удаления проекта',
            'path' => 'templates/project-delete.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('reports')->insertGetId([
            'template_id' => $templateId,
            'template_name' => 'Шаблон для удаления проекта',
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'specialist_id' => $project->specialist_id,
            'manager_id' => $user->id,
            'format' => 'docx',
            'created_by' => $user->id,
            'path' => 'reports/project-delete.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::findByName('manager');
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        return $user;
    }
}
