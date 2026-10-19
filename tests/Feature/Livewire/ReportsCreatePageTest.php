<?php

namespace Tests\Feature\Livewire;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\SidebarProjectAccess;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ReportsCreatePageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        session()->forget(SidebarProjectAccess::SESSION_KEY);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_period_defaults_to_current_month(): void
    {
        $this->actingAsEditor();

        $component = Livewire::test('pages::reports-create');

        $this->assertSame('2026-10-01', $component->get('from')->toDateString());
        $this->assertSame('2026-10-01', $component->get('to')->toDateString());
        $this->assertSame('docx', $component->get('format'));
        $this->assertFalse($component->get('hasPendingChanges'));
    }

    public function test_future_month_is_clamped_to_current_month(): void
    {
        $this->actingAsEditor();

        $component = Livewire::test('pages::reports-create')
            ->set('to', Carbon::parse('2026-12-01'));

        // Будущий месяц обрезается до текущего - снимок не меняется.
        $this->assertSame('2026-10-01', $component->get('to')->toDateString());
        $this->assertFalse($component->get('hasPendingChanges'));
    }

    public function test_sidebar_project_preselects_and_limits_options(): void
    {
        $user = $this->actingAsEditor();

        $own = $this->createProject('Own project', $user->id);
        $other = $this->createProject('Other project');

        session([SidebarProjectAccess::SESSION_KEY => $own->id]);

        $component = Livewire::test('pages::reports-create');

        $this->assertSame($own->id, $component->get('projectId'));
        $optionIds = array_column($component->get('projectOptions'), 'value');
        $this->assertSame([$own->id], $optionIds);
        $this->assertNotContains($other->id, $optionIds);
        $this->assertFalse($component->get('hasPendingChanges'));
    }

    public function test_self_user_sees_only_accessible_projects(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('seo');
        $role->syncPermissions([
            'edit reports',
            'read clients and projects self',
        ]);
        $viewer->assignRole($role);
        $this->actingAs($viewer);

        $own = $this->createProject('Accessible', $viewer->id);
        $foreign = $this->createProject('Hidden');

        $component = Livewire::test('pages::reports-create');
        $optionIds = array_column($component->get('projectOptions'), 'value');

        $this->assertContains($own->id, $optionIds);
        $this->assertNotContains($foreign->id, $optionIds);
    }

    public function test_submit_stays_disabled_until_project_and_template_chosen(): void
    {
        $this->actingAsEditor();

        $component = Livewire::test('pages::reports-create')
            ->set('from', Carbon::parse('2026-09-01'));

        $this->assertTrue($component->get('hasPendingChanges'));
        $this->assertFalse($component->get('canSubmit'));
        $component->assertSee('Сформировать и скачать');
        $component->assertDontSeeHtml('label="Сформировать"');
    }

    public function test_discard_restores_snapshot_and_hides_actions(): void
    {
        $this->actingAsEditor();

        $component = Livewire::test('pages::reports-create')
            ->set('from', Carbon::parse('2026-09-01'))
            ->call('discardChanges');

        $this->assertSame('2026-10-01', $component->get('from')->toDateString());
        $this->assertFalse($component->get('hasPendingChanges'));
        $component->assertDontSee('Сформировать и скачать');
    }

    private function actingAsEditor(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'edit reports',
            'read clients and projects all',
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function createProject(string $name, ?int $specialistId = null): Project
    {
        $client = Client::factory()->create([
            'manager_id' => User::factory()->create(['is_active' => true])->id,
        ]);

        return Project::factory()->create([
            'name' => $name,
            'client_id' => $client->id,
            'specialist_id' => $specialistId ?? User::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);
    }
}
