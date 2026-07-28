<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creates_roles_and_assigns_admin_to_user()
    {
        $roles = [
            'admin',
            'manager',
            'kr',
            'seo',
            'rucovotdelseo',
            'rucovotdelkp',
            'rucovotdelmanager',
            'office_manager',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
    }
}
