<?php

namespace Tests\Feature\SystemSettings;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientProjectCreatePermissionsTest extends TestCase
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

    public function test_list_page_disables_create_buttons_without_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects'))
            ->assertOk()
            ->assertSee(__('permissions.denied'), false)
            ->assertDontSee(
                'href="'.route('system-settings.clients-and-projects.projects.manage').'"',
                false
            );
    }

    public function test_list_page_enables_create_project_link_with_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
            'edit clients and projects all',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects'))
            ->assertOk()
            ->assertSee(
                'href="'.route('system-settings.clients-and-projects.projects.manage').'"',
                false
            );
    }

    public function test_create_project_url_forbidden_without_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read clients and projects all',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.clients-and-projects.projects.manage'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }
}
