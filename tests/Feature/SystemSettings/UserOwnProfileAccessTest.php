<?php

namespace Tests\Feature\SystemSettings;

use App\Enums\Role as RoleEnum;
use App\Models\Agency;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserOwnProfileAccessTest extends TestCase
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
        $role = Role::findByName(RoleEnum::MANAGER->value);
        $role->syncPermissions($permissions);

        $user = User::factory()->create([
            'is_active' => true,
            'first_name' => 'Иван',
            'last_name' => 'Тестов',
            'login' => 'manager_profile_'.uniqid(),
            'megaplan_id' => '1000001',
        ]);
        $user->assignRole($role);

        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);
        session(['current_agency_id' => $agency->id]);

        return $user;
    }

    public function test_own_profile_ok_without_users_section_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read channels',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users.edit', $user))
            ->assertOk()
            ->assertSee('Настройки профиля', false)
            ->assertSee('disabled', false);
    }

    public function test_foreign_profile_forbidden_without_users_section_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read channels',
        ]);

        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('system-settings.users.edit', $other))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_own_profile_save_updates_personal_fields_without_users_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read channels',
        ]);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.users-edit', ['user' => $user])
            ->set('form.first_name', 'Пётр')
            ->set('form.email', 'petr.profile@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Пётр', $user->first_name);
        $this->assertSame('petr.profile@example.com', $user->email);
    }

    public function test_own_profile_save_ignores_admin_field_tampering(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read channels',
        ]);

        $originalLogin = $user->login;
        $originalMegaplan = $user->megaplan_id;
        $adminRole = Role::findByName(RoleEnum::ADMIN->value);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.users-edit', ['user' => $user])
            ->set('form.first_name', 'Пётр')
            ->set('form.login', 'hacked_login')
            ->set('form.is_active', false)
            ->set('form.megaplan_id', '9999999')
            ->set('form.role_id', $adminRole->id)
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Пётр', $user->first_name);
        $this->assertSame($originalLogin, $user->login);
        $this->assertTrue((bool) $user->is_active);
        $this->assertSame($originalMegaplan, $user->megaplan_id);
        $this->assertTrue($user->hasRole(RoleEnum::MANAGER->value));
        $this->assertFalse($user->hasRole(RoleEnum::ADMIN->value));
    }

    public function test_users_list_still_forbidden_without_section_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read channels',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users'))
            ->assertForbidden()
            ->assertSee(__('permissions.denied'), false);
    }

    public function test_admin_fields_disabled_without_users_edit_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read channels',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users.edit', $user))
            ->assertOk()
            ->assertSee('Настройки профиля', false)
            ->assertSee('disabled', false);
    }

    public function test_admin_fields_editable_with_users_edit_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings users',
            'edit system settings users',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.users.edit', $user))
            ->assertOk()
            ->assertSee('Настройки профиля', false);
    }
}
