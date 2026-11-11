<?php

namespace Tests\Feature\Livewire;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Src\Application\Templates\TemplateTagReaderInterface;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class TemplatesPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findByName('admin'));
        $this->actingAs($user);
    }

    public function test_page_links_back_to_reports_and_to_report_creation(): void
    {
        Livewire::test('pages::templates')
            ->assertSeeHtml('href="'.route('reports').'"')
            ->assertSeeHtml('href="'.route('reports.create').'"');
    }

    public function test_upload_button_links_to_wizard(): void
    {
        Livewire::test('pages::templates')
            ->assertSeeHtml('href="'.route('reports.templates.upload').'"')
            ->assertDontSeeHtml('id="upload-input"');
    }

    public function test_empty_list_offers_template_upload(): void
    {
        DB::table('templates')->whereNotIn('id', DB::table('reports')->select('template_id'))->delete();

        if (DB::table('templates')->exists()) {
            $this->markTestSkipped('В БД есть шаблоны, привязанные к отчетам');
        }

        Livewire::test('pages::templates')
            ->assertSee('Нет шаблонов для отображения')
            ->assertSee('Загрузить шаблон')
            ->assertSeeHtml('href="'.route('reports.templates.upload').'"');
    }

    public function test_found_tags_are_hidden_behind_show_all_button(): void
    {
        $this->mock(TemplateTagReaderInterface::class, function ($mock): void {
            $mock->shouldReceive('read')->andReturnUsing(function (string $path): ?array {
                if ($path === 'templates/collapse-tags.docx') {
                    return ['current_year', 'period_from', 'period_to'];
                }

                return null;
            });
        });

        DB::table('templates')->insert([
            'name' => 'Шаблон со свёрткой тегов',
            'path' => 'templates/collapse-tags.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test('pages::templates')
            ->assertSee('Показать все теги (3)')
            ->assertSee('current_year')
            ->assertSee('period_from')
            ->assertSee('period_to')
            ->assertSeeHtml('max-height: 3.5rem');
    }

    public function test_list_shows_template_name_column(): void
    {
        DB::table('templates')->insert([
            'name' => 'Шаблон для теста страницы',
            'path' => 'templates/test.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test('pages::templates')
            ->assertSee('Название шаблона')
            ->assertSee('Найденные теги в шаблоне')
            ->assertDontSee('Название отчета')
            ->assertSee('Шаблон для теста страницы')
            ->assertSee('Не удалось прочитать файл');
    }

    public function test_read_only_user_cannot_upload_or_delete_templates(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions(['read report templates']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Только чтение.docx',
            'path' => 'templates/readonly.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test('pages::templates')
            ->assertDontSee('Загрузить шаблон')
            ->assertDontSeeHtml('href="'.route('reports.templates.upload').'"')
            ->assertDontSeeHtml('title="Редактировать"')
            ->assertDontSeeHtml('title="Удалить"')
            ->call('delete', $templateId)
            ->assertForbidden();

        Livewire::test('pages::templates-upload')
            ->assertForbidden();

        $this->get(route('reports.templates.upload'))
            ->assertForbidden();

        $this->get(route('reports.templates.upload', $templateId))
            ->assertForbidden();

        $this->assertDatabaseHas('templates', ['id' => $templateId]);
    }

    public function test_edit_user_can_upload_but_cannot_delete_templates(): void
    {
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read report templates',
            'edit report templates',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Без полного доступа.docx',
            'path' => 'templates/edit-only.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test('pages::templates')
            ->assertSee('Загрузить шаблон')
            ->assertSeeHtml('href="'.route('reports.templates.upload').'"')
            ->assertSeeHtml('href="'.route('reports.templates.upload', $templateId).'"')
            ->assertSeeHtml('title="Редактировать"')
            ->assertDontSeeHtml('title="Удалить"')
            ->call('delete', $templateId)
            ->assertForbidden();

        $this->assertDatabaseHas('templates', ['id' => $templateId]);
    }

    public function test_full_user_can_delete_template_used_in_reports(): void
    {
        $user = User::query()->firstOrFail();
        $client = Client::factory()->create([
            'manager_id' => $user->id,
        ]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $user->id,
            'is_active' => true,
            'project_type' => ProjectType::SEO_PROMOTION,
            'kpi' => Kpi::TRAFFIC,
        ]);
        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Шаблон с отчетом',
            'path' => 'templates/used.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reportId = DB::table('reports')->insertGetId([
            'template_id' => $templateId,
            'template_name' => 'Шаблон с отчетом',
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'specialist_id' => $project->specialist_id,
            'manager_id' => $user->id,
            'format' => 'docx',
            'created_by' => $user->id,
            'path' => 'reports/used.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test('pages::templates')
            ->assertDontSee('Нельзя удалить шаблон')
            ->assertSee('Готовые отчеты останутся')
            ->call('delete', $templateId)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('templates', ['id' => $templateId]);
        $this->assertDatabaseHas('reports', [
            'id' => $reportId,
            'template_id' => null,
            'template_name' => 'Шаблон с отчетом',
        ]);
    }
}
