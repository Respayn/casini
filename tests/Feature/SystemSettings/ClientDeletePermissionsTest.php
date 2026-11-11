<?php

namespace Tests\Feature\SystemSettings;

use App\Enums\PaymentSource;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Payment;
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

class ClientDeletePermissionsTest extends TestCase
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
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $client = Client::factory()->create(['manager_id' => $actor->id]);

        $this->actingAs($actor);

        Livewire::test('client.create-modal')
            ->call('onClientEdit', $client->id, $client->name, $client->inn, $client->initial_balance, $client->manager_id)
            ->assertSet('canDeleteClient', false)
            ->assertDontSee('Удалить клиента');
    }

    public function test_delete_button_visible_with_full_permission(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $client = Client::factory()->create(['manager_id' => $actor->id]);

        $this->actingAs($actor);

        Livewire::test('client.create-modal')
            ->call('onClientEdit', $client->id, $client->name, $client->inn, $client->initial_balance, $client->manager_id)
            ->assertSet('canDeleteClient', true)
            ->assertSee('Удалить клиента');
    }

    public function test_create_modal_hides_delete_button(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);

        $this->actingAs($actor);

        Livewire::test('client.create-modal')
            ->call('onClientCreate')
            ->assertSet('canDeleteClient', false)
            ->assertDontSee('Удалить клиента');
    }

    public function test_delete_blocked_when_client_has_projects(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $client = Client::factory()->create(['manager_id' => $actor->id]);
        Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $actor->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
        ]);

        $this->actingAs($actor);

        Livewire::test('client.create-modal')
            ->call('onClientEdit', $client->id, $client->name, $client->inn, $client->initial_balance, $client->manager_id)
            ->call('deleteClient')
            ->assertSet(
                'deleteError',
                'Нельзя удалить клиента: у него есть клиенто-проекты.'
            );

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_delete_blocked_when_client_has_payments(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $client = Client::factory()->create(['manager_id' => $actor->id]);
        Payment::query()->create([
            'number' => 'TEST-PAY-1',
            'source' => PaymentSource::FROM_1C,
            'received_date' => now()->toDateString(),
            'client_id' => $client->id,
        ]);

        $this->actingAs($actor);

        Livewire::test('client.create-modal')
            ->call('onClientEdit', $client->id, $client->name, $client->inn, $client->initial_balance, $client->manager_id)
            ->call('deleteClient')
            ->assertSet(
                'deleteError',
                'Нельзя удалить клиента: у него есть платежи.'
            );

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_delete_success_removes_client(): void
    {
        $actor = $this->userWithPermissions([
            'full clients and projects',
            'edit clients and projects all',
            'read clients and projects all',
        ]);
        $client = Client::factory()->create(['manager_id' => $actor->id]);

        $this->actingAs($actor);

        Livewire::test('client.create-modal')
            ->call('onClientEdit', $client->id, $client->name, $client->inn, $client->initial_balance, $client->manager_id)
            ->call('deleteClient')
            ->assertSet('deleteError', null)
            ->assertDispatched('client-saved')
            ->assertDispatched('modal-hide');

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
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
