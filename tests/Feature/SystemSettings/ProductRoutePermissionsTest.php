<?php

namespace Tests\Feature\SystemSettings;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductRoutePermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_user_without_statistics_permission_cannot_open_statistics(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read channels']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('statistics'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_user_with_statistics_permission_can_open_statistics(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read statistics']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('statistics'))
            ->assertOk();
    }

    public function test_user_without_channels_permission_cannot_open_channels(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read statistics']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('channels'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_user_with_system_settings_but_without_clients_projects_cannot_open_project_form(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read system settings']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects.projects.manage', ['projectId' => 1]))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_user_with_read_all_cannot_open_create_project_form(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read clients and projects all',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects.projects.manage'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_user_with_edit_all_is_not_forbidden_on_create_project_form(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read clients and projects all',
            'edit clients and projects all',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $response = $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects.projects.manage'));

        $this->assertNotSame(403, $response->status());
    }
}
