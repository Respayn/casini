<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use App\Support\SharedProductFilters;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SharedProductFiltersTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        session()->forget([
            SharedProductFilters::SHOW_INACTIVE_KEY,
            SharedProductFilters::INCLUDE_VAT_KEY,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findByName('admin'));
        $this->actingAs($user);
    }

    public function test_vat_flag_from_channels_is_checked_on_planning(): void
    {
        Livewire::test('pages::channels')
            ->set('queryData.includeVat', true);

        $this->assertTrue(session(SharedProductFilters::INCLUDE_VAT_KEY));

        Livewire::test('pages::planning')
            ->assertSet('includeVat', true);
    }

    public function test_inactive_flag_from_reports_is_checked_on_channels(): void
    {
        Livewire::test('pages::reports')
            ->set('showInactiveProjects', true);

        $this->assertTrue(session(SharedProductFilters::SHOW_INACTIVE_KEY));

        Livewire::test('pages::channels')
            ->assertSet('queryData.showInactive', true);
    }

    public function test_turning_vat_off_on_statistics_clears_it_for_planning(): void
    {
        session([SharedProductFilters::INCLUDE_VAT_KEY => true]);

        Livewire::test('pages::statistics')
            ->assertSet('queryData.includeVat', true)
            ->set('queryData.includeVat', false);

        $this->assertFalse(session(SharedProductFilters::INCLUDE_VAT_KEY));

        Livewire::test('pages::planning')
            ->assertSet('includeVat', false);
    }
}
