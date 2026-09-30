<?php

namespace Tests\Unit\Database\Seeders;

use App\Enums\PermissionGroup;
use App\Enums\Role as RoleEnum;
use Database\Seeders\MigrateSystemSettingsPermissionsSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MigrateSystemSettingsPermissionsSeederTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_copies_read_system_settings_to_new_sections(): void
    {
        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $manager->syncPermissions(['read system settings']);

        $this->seed(MigrateSystemSettingsPermissionsSeeder::class);

        $manager->refresh();

        $this->assertTrue($manager->hasPermissionTo('read system settings'));
        $this->assertTrue($manager->hasPermissionTo('read system settings dictionaries'));
        $this->assertTrue($manager->hasPermissionTo('read system settings users'));
        $this->assertTrue($manager->hasPermissionTo('read system settings roles and permissions'));
        $this->assertFalse($manager->hasPermissionTo('edit system settings dictionaries'));
    }

    public function test_copies_edit_and_full_levels(): void
    {
        $manager = Role::findByName(RoleEnum::MANAGER->value);
        $manager->syncPermissions([
            'edit system settings',
            'full system settings',
        ]);

        $this->seed(MigrateSystemSettingsPermissionsSeeder::class);

        $manager->refresh();

        foreach ([
            PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES,
            PermissionGroup::SYSTEM_SETTINGS_USERS,
            PermissionGroup::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS,
        ] as $group) {
            $this->assertTrue($manager->hasPermissionTo('edit '.$group->value));
            $this->assertTrue($manager->hasPermissionTo('full '.$group->value));
        }
    }
}
