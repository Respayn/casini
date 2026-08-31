<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\SidebarService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class SidebarServiceTest extends TestCase
{
    use DatabaseTransactions;

    private SidebarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->service = app(SidebarService::class);
    }

    public function test_get_role_options_uses_managers_label(): void
    {
        $managerRole = Role::findByName('manager');
        $managerRole->use_in_project_filter = true;
        $managerRole->use_in_managers_list = true;
        $managerRole->save();

        $options = $this->service->getRoleOptions();
        $option = collect($options)->firstWhere('value', (string) $managerRole->id);

        $this->assertNotNull($option);
        $this->assertSame('По менеджерам', $option['label']);
    }

    public function test_manager_sees_own_clients_and_active_projects(): void
    {
        $viewer = $this->actingAsUserWithPermission('read clients and projects all');

        $managerRole = Role::findByName('manager');
        $managerRole->use_in_project_filter = true;
        $managerRole->use_in_managers_list = true;
        $managerRole->save();

        $manager = User::factory()->create([
            'is_active' => true,
            'first_name' => 'Наталья',
            'last_name' => 'Басаргина',
        ]);
        $manager->assignRole($managerRole);

        $client = Client::factory()->create([
            'name' => "ИП Пахомчик Н. В.",
            'manager_id' => $manager->id,
        ]);

        $active = Project::factory()->create([
            'name' => 'Честный путь Екб',
            'client_id' => $client->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        Project::factory()->create([
            'name' => 'Неактивный',
            'client_id' => $client->id,
            'specialist_id' => $active->specialist_id,
            'is_active' => false,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        $employees = $this->service->getEmployees((string) $managerRole->id, null);

        $this->assertArrayHasKey($manager->id, $employees);
        $this->assertSame('Наталья Басаргина', $employees[$manager->id]->name);
        $this->assertArrayHasKey($client->id, $employees[$manager->id]->clients);
        $projects = $employees[$manager->id]->clients[$client->id]->projects;
        $this->assertArrayHasKey($active->id, $projects);
        $this->assertCount(1, $projects);
        $this->assertSame($viewer->id, auth()->id());

        // Один сотрудник в списке — дерево сразу раскрыто
        $this->assertTrue($employees[$manager->id]->open);
        $this->assertTrue($employees[$manager->id]->clients[$client->id]->open);
    }

    public function test_multiple_managers_stay_collapsed_by_default(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');

        $managerRole = Role::findByName('manager');
        $managerRole->use_in_project_filter = true;
        $managerRole->use_in_managers_list = true;
        $managerRole->save();

        $first = User::factory()->create(['is_active' => true, 'first_name' => 'Аня', 'last_name' => 'А']);
        $second = User::factory()->create(['is_active' => true, 'first_name' => 'Борис', 'last_name' => 'Б']);
        $first->assignRole($managerRole);
        $second->assignRole($managerRole);

        foreach ([$first, $second] as $manager) {
            $client = Client::factory()->create(['manager_id' => $manager->id]);
            Project::factory()->create([
                'client_id' => $client->id,
                'specialist_id' => User::factory()->create(['is_active' => true])->id,
                'is_active' => true,
                'project_type' => ProjectType::SEO_PROMOTION,
                'kpi' => Kpi::TRAFFIC,
            ]);
        }

        $employees = $this->service->getEmployees((string) $managerRole->id, null);

        $this->assertCount(2, $employees);
        $this->assertFalse($employees[$first->id]->open);
        $this->assertFalse($employees[$second->id]->open);
    }

    public function test_specialist_sees_only_own_projects_grouped_by_client(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');

        $seoRole = Role::findByName('seo');
        $seoRole->use_in_project_filter = true;
        $seoRole->use_in_managers_list = false;
        $seoRole->use_in_specialist_list = true;
        $seoRole->save();

        $specialist = User::factory()->create(['is_active' => true, 'first_name' => 'Иван', 'last_name' => 'Иванов']);
        $specialist->assignRole($seoRole);

        $other = User::factory()->create(['is_active' => true]);
        $client = Client::factory()->create(['manager_id' => User::factory()->create(['is_active' => true])->id]);

        $mine = Project::factory()->create([
            'name' => 'Мой проект',
            'client_id' => $client->id,
            'specialist_id' => $specialist->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        Project::factory()->create([
            'name' => 'Чужой проект',
            'client_id' => $client->id,
            'specialist_id' => $other->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        $employees = $this->service->getEmployees((string) $seoRole->id, null);

        $this->assertArrayHasKey($specialist->id, $employees);
        $this->assertCount(1, $employees);
        $this->assertTrue($employees[$specialist->id]->open);
        $this->assertTrue($employees[$specialist->id]->clients[$client->id]->open);
        $this->assertCount(1, $employees[$specialist->id]->clients);
        $this->assertArrayHasKey($mine->id, $employees[$specialist->id]->clients[$client->id]->projects);
        $this->assertCount(1, $employees[$specialist->id]->clients[$client->id]->projects);
    }

    public function test_self_permission_shows_only_current_user_node(): void
    {
        $managerRole = Role::findByName('manager');
        $managerRole->use_in_project_filter = true;
        $managerRole->use_in_managers_list = true;
        $managerRole->save();

        $self = User::factory()->create(['is_active' => true]);
        $selfRole = Role::findByName('manager');
        $selfRole->givePermissionTo('read clients and projects self');
        $self->assignRole($managerRole);
        $this->actingAs($self);

        $other = User::factory()->create(['is_active' => true]);
        $other->assignRole($managerRole);

        $selfClient = Client::factory()->create(['manager_id' => $self->id]);
        Project::factory()->create([
            'client_id' => $selfClient->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::CONTEXT_AD,
            'kpi' => Kpi::LEADS,
        ]);

        $otherClient = Client::factory()->create(['manager_id' => $other->id]);
        Project::factory()->create([
            'client_id' => $otherClient->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::CONTEXT_AD,
            'kpi' => Kpi::LEADS,
        ]);

        $employees = $this->service->getEmployees((string) $managerRole->id, null);

        $this->assertArrayHasKey($self->id, $employees);
        $this->assertArrayNotHasKey($other->id, $employees);
    }

    public function test_search_filters_by_project_name(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');

        $managerRole = Role::findByName('manager');
        $managerRole->use_in_project_filter = true;
        $managerRole->use_in_managers_list = true;
        $managerRole->save();

        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole($managerRole);

        $client = Client::factory()->create(['manager_id' => $manager->id, 'name' => 'Клиент А']);
        Project::factory()->create([
            'name' => 'estnuyput.ru',
            'client_id' => $client->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);
        Project::factory()->create([
            'name' => 'Другой',
            'client_id' => $client->id,
            'specialist_id' => User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        $employees = $this->service->getEmployees((string) $managerRole->id, 'estnuyput');

        $this->assertCount(1, $employees);
        $projects = $employees[$manager->id]->clients[$client->id]->projects;
        $this->assertCount(1, $projects);
        $this->assertSame('estnuyput.ru', collect($projects)->first()->name);
    }

    public function test_role_without_users_returns_empty_list(): void
    {
        $this->actingAsUserWithPermission('read clients and projects all');

        $emptyRole = Role::create([
            'name' => 'sidebar_empty_role_'.uniqid(),
            'display_name' => 'Пустая роль сайдбара',
            'guard_name' => 'web',
            'use_in_project_filter' => true,
            'use_in_managers_list' => true,
        ]);

        $employees = $this->service->getEmployees((string) $emptyRole->id, null);

        $this->assertSame([], $employees);
    }

    public function test_without_clients_permissions_returns_empty_list(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('office_manager');
        $role->syncPermissions(['read channels']);
        $user->assignRole($role);
        $this->actingAs($user);

        $managerRole = Role::findByName('manager');
        $managerRole->use_in_project_filter = true;
        $managerRole->use_in_managers_list = true;
        $managerRole->save();

        $employees = $this->service->getEmployees((string) $managerRole->id, null);

        $this->assertSame([], $employees);
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
