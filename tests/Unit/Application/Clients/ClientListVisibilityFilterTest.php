<?php

namespace Tests\Unit\Application\Clients;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Src\Application\Clients\GetClientsWithProjects\ClientDto;
use Src\Application\Clients\GetClientsWithProjects\ClientListVisibilityFilter;
use Src\Application\Clients\GetClientsWithProjects\ClientProjectDto;
use Tests\TestCase;

class ClientListVisibilityFilterTest extends TestCase
{
    use DatabaseTransactions;

    private ClientListVisibilityFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->filter = new ClientListVisibilityFilter();
    }

    public function test_returns_empty_list_without_self_or_all_permissions(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('manager');
        $role->syncPermissions(['read system settings']);
        $user->assignRole($role);

        $clients = [
            new ClientDto(1, 'Client A', '123', 0.0, 99, [
                new ClientProjectDto(10, 'Project', 'SEO', 50),
            ]),
        ];

        $result = $this->filter->filterForUser($clients, $user);

        $this->assertSame([], $result);
    }

    public function test_self_permission_shows_projects_where_user_is_client_manager(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('manager');
        $role->syncPermissions(['read clients and projects self']);
        $user->assignRole($role);

        $clients = [
            new ClientDto(1, 'Client A', '123', 0.0, $user->id, [
                new ClientProjectDto(10, 'Project 1', 'SEO', 999),
                new ClientProjectDto(11, 'Project 2', 'SEO', 888),
            ]),
        ];

        $result = $this->filter->filterForUser($clients, $user);

        $this->assertCount(1, $result);
        $this->assertCount(2, $result[0]->projects);
    }

    public function test_self_permission_shows_only_assigned_projects_for_specialist(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('manager');
        $role->syncPermissions(['read clients and projects self']);
        $user->assignRole($role);

        $clients = [
            new ClientDto(1, 'Client A', '123', 0.0, 999, [
                new ClientProjectDto(10, 'Mine', 'SEO', $user->id),
                new ClientProjectDto(11, 'Not mine', 'SEO', 888),
            ]),
        ];

        $result = $this->filter->filterForUser($clients, $user);

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]->projects);
        $this->assertSame(10, $result[0]->projects[0]->id);
    }

    public function test_all_permission_returns_full_list(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('manager');
        $role->syncPermissions(['read clients and projects all']);
        $user->assignRole($role);

        $clients = [
            new ClientDto(1, 'Client A', '123', 0.0, 999, [
                new ClientProjectDto(10, 'Project', 'SEO', 888),
            ]),
        ];

        $result = $this->filter->filterForUser($clients, $user);

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]->projects);
    }
}
