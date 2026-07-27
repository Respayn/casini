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
}
