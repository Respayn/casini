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
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ReportsListPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        session()->forget(SidebarProjectAccess::SESSION_KEY);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findByName('admin'));
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_period_defaults_to_current_month(): void
    {
        $component = Livewire::test('pages::reports');

        $this->assertSame('2026-10-01', $component->get('periodFrom')->toDateString());
        $this->assertSame('2026-10-01', $component->get('periodTo')->toDateString());
    }

    public function test_future_month_is_clamped_to_current_month(): void
    {
        $component = Livewire::test('pages::reports')
            ->set('periodTo', Carbon::parse('2026-12-01'));

        $this->assertSame('2026-10-01', $component->get('periodTo')->toDateString());
    }

    public function test_period_end_follows_start_when_start_is_later(): void
    {
        $component = Livewire::test('pages::reports')
            ->set('periodFrom', Carbon::parse('2026-09-01'))
            ->set('periodTo', Carbon::parse('2026-07-01'));

        $this->assertSame('2026-09-01', $component->get('periodFrom')->toDateString());
        $this->assertSame('2026-09-01', $component->get('periodTo')->toDateString());
    }

    public function test_status_columns_are_removed(): void
    {
        $component = Livewire::test('pages::reports');

        $keys = array_column($component->get('columnSettings'), 'key');

        $this->assertNotContains('is_ready', $keys);
        $this->assertNotContains('is_accepted', $keys);
        $this->assertNotContains('is_sent', $keys);
        $component
            ->assertDontSee('Отчет готов')
            ->assertDontSee('Отчет принят менеджером')
            ->assertDontSee('Отчет отправлен клиенту');
    }

    public function test_empty_list_offers_report_creation(): void
    {
        $html = Livewire::test('pages::reports')->html();

        if (str_contains($html, '<table')) {
            $this->markTestSkipped('В БД уже есть отчеты за текущий период');
        }

        Livewire::test('pages::reports')
            ->assertSee('Нет отчетов для отображения')
            ->assertDontSee('Добавить клиенто-проект')
            ->assertSeeHtml('href="'.route('reports.create').'"');
    }

    public function test_table_stretches_to_full_width(): void
    {
        $html = Livewire::test('pages::reports')->html();

        if (! str_contains($html, '<table')) {
            $this->markTestSkipped('Нет отчетов для проверки ширины таблицы');
        }

        $this->assertStringContainsString('class="text-sm w-full"', $html);
    }

    public function test_actions_column_offers_download_edit_and_delete(): void
    {
        [$reportId] = $this->insertReport();

        Livewire::test('pages::reports')
            ->assertSee('Действия')
            ->assertDontSeeHtml('>Скачать</')
            ->assertSeeHtml('title="Скачать"')
            ->assertSeeHtml('title="Редактировать"')
            ->assertSeeHtml('href="'.route('reports.create', $reportId).'"')
            ->assertSeeHtml('title="Удалить"');
    }

    public function test_read_only_user_cannot_edit_or_delete_report(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read reports']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        [$reportId] = $this->insertReport($user);

        Livewire::test('pages::reports')
            ->assertSeeHtml('title="Скачать"')
            ->assertDontSeeHtml('title="Редактировать"')
            ->assertDontSeeHtml('title="Удалить"')
            ->call('delete', $reportId)
            ->assertForbidden();

        $this->assertDatabaseHas('reports', ['id' => $reportId]);

        Livewire::test('pages::reports-create', ['reportId' => $reportId])
            ->assertForbidden();

        $this->get(route('reports.create', $reportId))
            ->assertForbidden();
    }

    public function test_edit_user_can_edit_but_cannot_delete_report(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read reports',
            'edit reports',
            'read clients and projects all',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        [$reportId] = $this->insertReport($user);

        Livewire::test('pages::reports')
            ->assertSeeHtml('title="Редактировать"')
            ->assertSeeHtml('href="'.route('reports.create', $reportId).'"')
            ->assertDontSeeHtml('title="Удалить"')
            ->call('delete', $reportId)
            ->assertForbidden();

        $this->assertDatabaseHas('reports', ['id' => $reportId]);
    }

    public function test_full_user_can_delete_report(): void
    {
        [$reportId] = $this->insertReport();

        Livewire::test('pages::reports')
            ->call('delete', $reportId)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('reports', ['id' => $reportId]);
    }

    public function test_templates_button_is_secondary(): void
    {
        $html = Livewire::test('pages::reports')->html();

        $this->assertStringNotContainsString('bg-primary', $this->linkClasses($html, route('reports.templates')));
        $this->assertStringContainsString('bg-primary', $this->linkClasses($html, route('reports.create')));
    }

    /**
     * @return array{0: int, 1: Project, 2: int}
     */
    private function insertReport(?User $user = null): array
    {
        $user ??= User::query()->firstOrFail();
        $project = $this->createProject($user);
        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Шаблон для действий',
            'path' => 'templates/actions.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reportId = DB::table('reports')->insertGetId([
            'template_id' => $templateId,
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'specialist_id' => $project->specialist_id,
            'manager_id' => $user->id,
            'format' => 'docx',
            'created_by' => $user->id,
            'path' => 'reports/actions.docx',
            'created_at' => '2026-10-15 12:00:00',
            'updated_at' => '2026-10-15 12:00:00',
        ]);

        return [$reportId, $project, $templateId];
    }

    private function createProject(User $user): Project
    {
        $client = Client::factory()->create([
            'manager_id' => $user->id,
        ]);

        return Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);
    }

    private function linkClasses(string $html, string $href): string
    {
        $pattern = '/<a href="'.preg_quote($href, '/').'"[^>]*class="([^"]*)"/u';

        $this->assertSame(1, preg_match($pattern, $html, $matches), 'Нет ссылки '.$href);

        return $matches[1];
    }
}
