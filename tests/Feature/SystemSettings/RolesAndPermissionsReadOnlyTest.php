<?php

namespace Tests\Feature\SystemSettings;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsReadOnlyTest extends TestCase
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
        $role = Role::findByName('manager');
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);
        session(['current_agency_id' => $agency->id]);

        return $user;
    }

    public function test_page_ok_with_read_only_and_shows_denied_tooltip(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings roles and permissions',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.roles-and-permissions'))
            ->assertOk()
            ->assertSee('canEditPage: false', false)
            ->assertSee(__('permissions.denied'), false)
            ->assertSee('disabled', false);
    }

    public function test_page_enables_edit_mode_with_edit_permission(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings roles and permissions',
            'edit system settings roles and permissions',
        ]);

        $this->actingAs($user)
            ->get(route('system-settings.roles-and-permissions'))
            ->assertOk()
            ->assertSee('canEditPage: true', false);
    }

    public function test_save_forbidden_without_edit(): void
    {
        $user = $this->actingManagerWithPermissions([
            'read system settings roles and permissions',
        ]);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.roles-and-permissions')
            ->call('save')
            ->assertForbidden();
    }
}
