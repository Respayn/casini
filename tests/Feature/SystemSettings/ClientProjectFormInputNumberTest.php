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
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ClientProjectFormInputNumberTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function actingEditor(): User
    {
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read clients and projects all',
            'edit clients and projects all',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        return $user;
    }

    private function projectForUser(User $user): Project
    {
        $client = Client::factory()->create(['manager_id' => $user->id]);

        return Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
            'is_active' => true,
            'is_internal' => false,
        ]);
    }

    #[Test]
    public function test_client_payment_visible_without_bonuses_toggle(): void
    {
        $user = $this->actingEditor();
        $project = $this->projectForUser($user);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->assertSet('bonusGuaranteeForm.bonusesEnabled', false)
            ->assertSee('Чек клиента', false)
            ->assertSee('inputmode="decimal"', false)
            ->assertSee('₽', false)
            ->assertDontSee('type="number"', false);
    }

    #[Test]
    public function test_bonus_fields_use_input_number_markup(): void
    {
        $user = $this->actingEditor();
        $project = $this->projectForUser($user);

        $this->actingAs($user);

        Livewire::test('pages::system-settings.client-project-form', ['projectId' => $project->id])
            ->set('bonusGuaranteeForm.bonusesEnabled', true)
            ->assertSee('Чек клиента', false)
            ->assertSee('inputmode="decimal"', false)
            ->assertSee('₽', false)
            ->assertSee('>%', false)
            ->assertSee('bonusGuaranteeForm.intervals.0.fromPercentage', false)
            ->assertDontSee('type="number"', false);
    }
}
