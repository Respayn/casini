<?php

namespace Tests\Feature\SystemSettings;

use App\Enums\PermissionGroup;
use App\Models\Agency;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemSettingsSectionRoutePermissionsTest extends TestCase
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

    public function test_dictionaries_forbidden_without_section_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.dictionaries'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_dictionaries_ok_with_read_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings dictionaries',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.dictionaries'))
            ->assertOk();
    }

    public function test_users_forbidden_without_section_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_users_ok_with_read_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings users',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users'))
            ->assertOk();
    }

    public function test_roles_and_permissions_forbidden_without_section_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.roles-and-permissions'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_roles_and_permissions_ok_with_read_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings roles and permissions',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.roles-and-permissions'))
            ->assertOk();
    }

    public function test_agency_ok_with_edit_system_settings(): void
    {
        $user = $this->actingManagerWithPermissions([
            'edit system settings',
        ]);

        $response = $this->actingAs($user)
            ->get(route('system-settings.agency'));

        $this->assertNotSame(403, $response->status());
    }

    public function test_agency_forbidden_without_agency_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings dictionaries',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.agency'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_new_groups_are_full_access_locked_for_non_admin(): void
    {
        foreach ([
            PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES,
            PermissionGroup::SYSTEM_SETTINGS_USERS,
            PermissionGroup::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS,
        ] as $group) {
            $this->assertTrue($group->isFullAccessLockedForNonAdmin());
            $this->assertFalse($group->isEditAccessLockedForNonAdmin());
            $this->assertFalse($group->isSecondary());
        }
    }
}
