<?php

namespace Tests\Feature;

use App\Models\AgencySetting;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_returns_all_users_as_user_data_collection()
    {
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->assignRole('admin');
        }

        $repo = new UserRepository();
        $result = $repo->all();

        $this->assertCount(3, $result);
        foreach ($result as $userData) {
            $this->assertInstanceOf(\App\Data\UserData::class, $userData);
        }
    }

    public function test_can_filter_users_by_agency_and_status()
    {
        $agency = AgencySetting::factory()->create();
        $otherAgency = AgencySetting::factory()->create();

        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $foreignUser = User::factory()->create(['is_active' => true]);

        $activeUser->assignRole('admin');
        $inactiveUser->assignRole('admin');
        $foreignUser->assignRole('admin');

        $activeUser->agencies()->attach($agency->id);
        $inactiveUser->agencies()->attach($agency->id);
        $foreignUser->agencies()->attach($otherAgency->id);

        $repo = new UserRepository();

        $resultAll = $repo->allByAgency($agency->id);
        $resultActive = $repo->allByAgency($agency->id, true);
        $resultInactive = $repo->allByAgency($agency->id, false);

        $this->assertCount(2, $resultAll);
        $this->assertCount(1, $resultActive);
        $this->assertCount(1, $resultInactive);

        $this->assertEquals($activeUser->id, $resultActive->first()->id);
        $this->assertEquals($inactiveUser->id, $resultInactive->first()->id);
    }

    public function test_can_find_user_by_id()
    {
        $user = User::factory()->create(['login' => 'someuser']);
        $user->assignRole('admin');

        $repo = new UserRepository();

        $found = $repo->find($user->id);
        $this->assertNotNull($found);
        $this->assertEquals('someuser', $found->login);
    }

    public function test_can_find_user_by_login()
    {
        $user = User::factory()->create(['login' => 'uniquelogin']);
        $user->assignRole('admin');

        $repo = new UserRepository();

        $found = $repo->findByLogin('uniquelogin');
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_can_find_active_user_by_email()
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'is_active' => true,
        ]);
        $inactive = User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);
        $user->assignRole('admin');
        $inactive->assignRole('admin');

        $repo = new UserRepository();

        $found = $repo->findActiveByEmail('active@example.com');
        $this->assertNotNull($found);
        $this->assertContains('admin', is_iterable($user->roles) ? collect($user->roles)->pluck('name')->toArray() : []);

        $notFound = $repo->findActiveByEmail('inactive@example.com');
        $this->assertNull($notFound);
    }
}
