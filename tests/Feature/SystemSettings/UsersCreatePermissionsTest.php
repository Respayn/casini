<?php

namespace Tests\Feature\SystemSettings;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersCreatePermissionsTest extends TestCase
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
        session(['current_agency_id' => $agency->id]);

        return $user;
    }

    public function test_list_page_disables_create_button_without_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings users',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users'))
            ->assertOk()
            ->assertSee(__('permissions.denied'), false)
            ->assertDontSee(
                'href="'.route('system-settings.users.create').'"',
                false
            );
    }

    public function test_list_page_enables_create_link_with_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings users',
            'edit system settings users',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users'))
            ->assertOk()
            ->assertSee(
                'href="'.route('system-settings.users.create').'"',
                false
            );
    }

    public function test_create_url_forbidden_without_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings users',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users.create'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_create_url_ok_with_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings users',
            'edit system settings users',
        ]);

        $response = $this->actingAs($user)
            ->get(route('system-settings.users.create'));

        $this->assertNotSame(403, $response->status());
    }
}
