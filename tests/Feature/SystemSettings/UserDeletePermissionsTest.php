<?php

namespace Tests\Feature\SystemSettings;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class UserDeletePermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_delete_button_hidden_without_full_permission(): void
    {
        $actor = $this->userWithPermissions([
            'edit system settings users',
        ]);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.users-edit', ['user' => $target])
            ->assertSet('canDeleteUser', false)
            ->assertDontSee('Удалить пользователя');
    }

    public function test_delete_button_visible_with_full_permission(): void
    {
        $actor = $this->userWithPermissions([
            'full system settings users',
        ]);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.users-edit', ['user' => $target])
            ->assertSet('canDeleteUser', true)
            ->assertSee('Удалить пользователя');
    }

    public function test_own_profile_hides_delete_button(): void
    {
        $actor = $this->userWithPermissions([
            'full system settings users',
        ]);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.users-edit', ['user' => $actor])
            ->assertSet('canDeleteUser', false)
            ->assertDontSee('Удалить пользователя');
    }

    public function test_delete_blocked_when_user_manages_client(): void
    {
        $actor = $this->userWithPermissions([
            'full system settings users',
        ]);
        $target = User::factory()->create(['is_active' => true]);
        Client::factory()->create(['manager_id' => $target->id]);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.users-edit', ['user' => $target])
            ->call('deleteUser')
            ->assertSet(
                'deleteError',
                'Нельзя удалить пользователя: на нем есть клиенты или клиенто-проекты.'
            )
            ->assertNoRedirect();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_delete_blocked_when_user_is_project_specialist(): void
    {
        $actor = $this->userWithPermissions([
            'full system settings users',
        ]);
        $target = User::factory()->create(['is_active' => true]);
        $manager = User::factory()->create(['is_active' => true]);
        $client = Client::factory()->create(['manager_id' => $manager->id]);
        Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $target->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.users-edit', ['user' => $target])
            ->call('deleteUser')
            ->assertSet(
                'deleteError',
                'Нельзя удалить пользователя: на нем есть клиенты или клиенто-проекты.'
            );

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_delete_succeeds_for_unlinked_user(): void
    {
        $actor = $this->userWithPermissions([
            'full system settings users',
        ]);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor);

        Livewire::test('pages::system-settings.users-edit', ['user' => $target])
            ->call('deleteUser')
            ->assertRedirect(route('system-settings.users', absolute: false));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::findByName('manager');
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        return $user;
    }
}
