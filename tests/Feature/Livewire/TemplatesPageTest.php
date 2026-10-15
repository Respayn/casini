<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
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

    public function test_empty_list_offers_template_upload(): void
    {
        DB::table('templates')->whereNotIn('id', DB::table('reports')->select('template_id'))->delete();

        if (DB::table('templates')->exists()) {
            $this->markTestSkipped('В БД есть шаблоны, привязанные к отчетам');
        }

        Livewire::test('pages::templates')
            ->assertSee('Нет шаблонов для отображения')
            ->assertSee('Загрузить шаблон');
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

    public function test_non_docx_upload_shows_clear_error(): void
    {
        Livewire::test('pages::templates')
            ->set('newTemplate', UploadedFile::fake()->create('template.pdf', 10, 'application/pdf'))
            ->assertHasErrors(['newTemplate' => 'mimes'])
            ->assertSee('Шаблон должен быть файлом Word в формате .docx.');
    }
}
