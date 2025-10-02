<?php

namespace Database\Seeders;

use App\Enums\PermissionGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accessLevels = [
            'read',
            'edit',
            'full'
        ];

        $adminRole = Role::findByName('admin');

        foreach (PermissionGroup::flatValues() as $group) {
            foreach ($accessLevels as $level) {
                $permission = Permission::updateOrCreate(
                    ['name' => $level . ' ' . $group],
                    ['group' => $group]
                );

                $adminRole->givePermissionTo($permission);
            }
        }
    }
}
