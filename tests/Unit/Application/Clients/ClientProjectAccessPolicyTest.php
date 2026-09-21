<?php

namespace Tests\Unit\Application\Clients;

use App\Data\ProjectData;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Src\Application\Clients\Access\ClientProjectAccessPolicy;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ClientProjectAccessPolicyTest extends TestCase
{
    use DatabaseTransactions;

    private ClientProjectAccessPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->policy = new ClientProjectAccessPolicy;
    }

    private function project(int $specialistId = 5): ProjectData
    {
        return new ProjectData(
            id: 1,
            name: 'Test',
            domain: 'https://example.com',
            client_id: 10,
            specialist_id: $specialistId,
            project_type: ProjectType::SEO_PROMOTION,
            kpi: Kpi::TRAFFIC,
            is_active: true,
            is_internal: false,
            traffic_attribution: null,
            metrika_counter: null,
            metrika_targets: null,
            google_ads_client_id: null,
            contract_number: null,
            additional_contract_number: null,
            recommendation_url: null,
            legal_entity: null,
            inn: null,
        );
    }

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::findByName('manager');
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_all_permission_can_view_any_project(): void
    {
        $user = $this->userWithPermissions(['read clients and projects all']);

        $this->assertTrue($this->policy->userCanViewProject($user, $this->project(99), 88));
    }

    public function test_self_permission_allows_client_manager(): void
    {
        $user = $this->userWithPermissions(['read clients and projects self']);

        $this->assertTrue($this->policy->userCanViewProject($user, $this->project(99), $user->id));
    }

    public function test_self_permission_allows_project_specialist(): void
    {
        $user = $this->userWithPermissions(['read clients and projects self']);

        $this->assertTrue($this->policy->userCanViewProject($user, $this->project($user->id), 88));
    }

    public function test_self_permission_denies_unrelated_user(): void
    {
        $user = $this->userWithPermissions(['read clients and projects self']);

        $this->assertFalse($this->policy->userCanViewProject($user, $this->project(99), 88));
    }

    public function test_parent_only_permission_denies_project_view(): void
    {
        $user = $this->userWithPermissions(['read clients and projects']);

        $this->assertFalse($this->policy->userCanViewProject($user, $this->project($user->id), $user->id));
    }
}
