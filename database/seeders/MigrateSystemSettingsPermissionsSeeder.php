<?php

namespace Database\Seeders;

use App\Enums\PermissionGroup;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Копирует уровни доступа «system settings» на три новых раздела настроек.
 * Старые права на агентство (system settings) не снимает.
 *
 * На новой среде после PermissionSeeder:
 * php artisan db:seed --class=MigrateSystemSettingsPermissionsSeeder --force
 *
 * Не подключён к DatabaseSeeder — одноразовый перенос существующих ролей.
 */
class MigrateSystemSettingsPermissionsSeeder extends Seeder
{
    /**
     * @var list<PermissionGroup>
     */
    private array $targetGroups = [
        PermissionGroup::SYSTEM_SETTINGS_DICTIONARIES,
        PermissionGroup::SYSTEM_SETTINGS_USERS,
        PermissionGroup::SYSTEM_SETTINGS_ROLES_AND_PERMISSIONS,
    ];

    /**
     * @var list<string>
     */
    private array $levels = ['read', 'edit', 'full'];

    public function run(): void
    {
        foreach (Role::query()->get() as $role) {
            if ($role->name === RoleEnum::ADMIN->value) {
                continue;
            }

            foreach ($this->levels as $level) {
                $sourcePermission = $level.' '.PermissionGroup::SYSTEM_SETTINGS->value;

                if (! $role->hasPermissionTo($sourcePermission)) {
                    continue;
                }

                foreach ($this->targetGroups as $group) {
                    $targetName = $level.' '.$group->value;
                    $permission = Permission::findByName($targetName);
                    $role->givePermissionTo($permission);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
