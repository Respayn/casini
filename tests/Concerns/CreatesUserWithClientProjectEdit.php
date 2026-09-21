<?php

namespace Tests\Concerns;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Spatie\Permission\Models\Role;

trait CreatesUserWithClientProjectEdit
{
    protected function createUserWithClientProjectEdit(): User
    {
        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read clients and projects all',
            'edit clients and projects all',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        return $user;
    }
}
