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

class UserBitrix24IdTest extends TestCase
{
    use DatabaseTransactions;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $role = Role::findByName(RoleEnum::MANAGER->value);
        $role->syncPermissions([
            'read system settings users',
            'edit system settings users',
        ]);

        $admin = User::factory()->create([
            'is_active' => true,
            'login' => 'bitrix_admin_'.uniqid(),
        ]);
        $admin->assignRole($role);

        $agency = Agency::factory()->create();
        $admin->agencies()->attach($agency->id);
        session(['current_agency_id' => $agency->id]);

        $this->target = User::factory()->create([
            'is_active' => true,
            'login' => 'bitrix_target_'.uniqid(),
            'megaplan_id' => '1000272',
        ]);

        $this->actingAs($admin);
    }

    public function test_saves_numeric_bitrix24_id_and_keeps_megaplan_id(): void
    {
        Livewire::test('pages::system-settings.users-edit', ['user' => $this->target])
            ->set('form.bitrix24_id', '42')
            ->call('save')
            ->assertHasNoErrors();

        $this->target->refresh();

        $this->assertSame(42, $this->target->bitrix24_id);
        $this->assertSame('1000272', $this->target->megaplan_id);
    }

    public function test_empty_bitrix24_id_is_saved_as_null(): void
    {
        $this->target->update(['bitrix24_id' => 42]);

        Livewire::test('pages::system-settings.users-edit', ['user' => $this->target])
            ->set('form.bitrix24_id', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->target->refresh()->bitrix24_id);
    }

    public function test_non_numeric_bitrix24_id_is_rejected(): void
    {
        Livewire::test('pages::system-settings.users-edit', ['user' => $this->target])
            ->set('form.bitrix24_id', 'абв')
            ->call('save')
            ->assertHasErrors(['form.bitrix24_id']);

        $this->assertNull($this->target->refresh()->bitrix24_id);
    }

    public function test_bitrix24_id_taken_by_another_user_is_rejected(): void
    {
        User::factory()->create([
            'login' => 'bitrix_other_'.uniqid(),
            'bitrix24_id' => 42,
        ]);

        Livewire::test('pages::system-settings.users-edit', ['user' => $this->target])
            ->set('form.bitrix24_id', '42')
            ->call('save')
            ->assertHasErrors(['form.bitrix24_id']);

        $this->assertNull($this->target->refresh()->bitrix24_id);
    }
}
