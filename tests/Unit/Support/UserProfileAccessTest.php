<?php

namespace Tests\Unit\Support;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Support\UserProfileAccess;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserProfileAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_merge_save_payload_strips_admin_fields_without_edit(): void
    {
        $role = Role::findByName(RoleEnum::MANAGER->value);
        $role->syncPermissions(['read channels']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $payload = UserProfileAccess::mergeSavePayload($user, [
            'first_name' => 'Пётр',
            'email' => 'petr@example.com',
            'login' => 'hacked',
            'is_active' => false,
            'role_id' => 1,
            'rate_id' => 2,
            'megaplan_id' => '999',
            'enable_notifications' => true,
        ], $user);

        $this->assertSame([
            'first_name' => 'Пётр',
            'email' => 'petr@example.com',
            'enable_notifications' => true,
        ], $payload);
    }

    public function test_merge_save_payload_keeps_admin_fields_with_edit(): void
    {
        $role = Role::findByName(RoleEnum::MANAGER->value);
        $role->syncPermissions([
            'edit system settings users',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $payload = [
            'first_name' => 'Пётр',
            'login' => 'new_login',
            'is_active' => false,
            'megaplan_id' => '999',
        ];

        $this->assertSame(
            $payload,
            UserProfileAccess::mergeSavePayload($user, $payload, $user)
        );
    }
}
