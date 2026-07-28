<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\ClientsAndProjectsPermissions;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientsAndProjectsPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_read_only_user_cannot_edit(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read clients and projects all']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertFalse(ClientsAndProjectsPermissions::userCanEdit($user));
    }

    public function test_edit_self_user_can_edit(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['edit clients and projects self']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertTrue(ClientsAndProjectsPermissions::userCanEdit($user));
    }

    public function test_ensure_user_can_edit_throws_without_permission(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read clients and projects all']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->expectException(UnauthorizedException::class);

        ClientsAndProjectsPermissions::ensureUserCanEdit($user);
    }

    public function test_user_can_read_with_parent_permission(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read clients and projects']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $this->assertTrue(ClientsAndProjectsPermissions::userCanRead($user));
        $this->assertFalse(ClientsAndProjectsPermissions::userCanEdit($user));
    }

    public function test_middleware_includes_parent_self_and_all(): void
    {
        $middleware = ClientsAndProjectsPermissions::middleware();

        $this->assertStringContainsString('read clients and projects|', $middleware);
        $this->assertStringContainsString('read clients and projects self|', $middleware);
        $this->assertStringContainsString('read clients and projects all|', $middleware);
        $this->assertStringStartsWith('permission:', $middleware);
    }
}
