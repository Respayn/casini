<?php

namespace Tests\Unit\Support;

use App\Enums\PermissionGroup;
use App\Models\User;
use App\Support\SystemSettingsSectionPermissions;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemSettingsSectionPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_user_can_read_with_edit_permission(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['edit system settings dictionaries']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertTrue(SystemSettingsSectionPermissions::userCanRead(
            PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES,
            $user
        ));
        $this->assertTrue(SystemSettingsSectionPermissions::userCanEdit(
            PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES,
            $user
        ));
    }

    public function test_middleware_includes_read_edit_full(): void
    {
        $middleware = SystemSettingsSectionPermissions::middleware(PermissionGroup::SYSTEM_SETTINGS_USERS);

        $this->assertSame(
            'permission:read system settings users|edit system settings users|full system settings users',
            $middleware
        );
    }

    public function test_any_settings_section_true_for_clients_only(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read clients and projects all']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertTrue(SystemSettingsSectionPermissions::userCanAccessAnySettingsSection($user));
        $this->assertSame(
            'system-settings.clients-and-projects',
            SystemSettingsSectionPermissions::firstAccessibleSettingsRouteName($user)
        );
    }

    public function test_any_settings_section_true_for_dictionaries_only(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read system settings dictionaries']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertTrue(SystemSettingsSectionPermissions::userCanAccessAnySettingsSection($user));
        $this->assertSame(
            'system-settings.dictionaries',
            SystemSettingsSectionPermissions::firstAccessibleSettingsRouteName($user)
        );
    }

    public function test_any_settings_section_false_without_settings_products(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read channels']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertFalse(SystemSettingsSectionPermissions::userCanAccessAnySettingsSection($user));
        $this->assertNull(SystemSettingsSectionPermissions::firstAccessibleSettingsRouteName($user));
    }

    public function test_first_accessible_route_prefers_roles_over_clients(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read system settings roles and permissions',
            'read clients and projects all',
            'read system settings dictionaries',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertSame(
            'system-settings.roles-and-permissions',
            SystemSettingsSectionPermissions::firstAccessibleSettingsRouteName($user)
        );
    }
}
