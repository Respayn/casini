<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TemplatesUploadPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.templates.upload'))
            ->assertRedirect(route('login'));
    }

    public function test_page_shows_wizard_shell_and_tags(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->assertSeeHtml('href="'.route('reports.templates').'"')
            ->assertSee('Создание шаблона')
            ->assertSee('Следующий шаг')
            ->assertSee('Отменить')
            ->assertSee('Откройте файл Word.')
            ->assertDontSee('дополнительное поле')
            ->assertDontSee('ссылка=да')
            ->set('showTags', true)
            ->assertSee('{{project_name}}')
            ->assertSee('{{yd.table}}')
            ->assertDontSee('Популярные теги')
            ->assertDontSee('Весь список тегов')
            ->assertDontSee('Категория')
            ->assertSee('Тег')
            ->assertSee('Формат отображения')
            ->assertSee('текст')
            ->assertSee('изображение')
            ->assertSee('таблица')
            ->assertSeeHtml('title="Скопировать"')
            ->assertSeeHtml('aria-label="Скопировать"')
            ->assertSeeHtml('data-copy="{{project_name}}"')
            ->assertDontSee('@js')
            ->set('step', 2)
            ->assertSee('Файл шаблона');
    }

    public function test_cancel_links_to_templates_list(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->assertSeeHtml('href="'.route('reports.templates').'"')
            ->assertSee('Отменить');
    }

    public function test_non_docx_upload_shows_clear_error(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->set('newTemplate', UploadedFile::fake()->create('template.pdf', 10, 'application/pdf'))
            ->assertHasErrors(['newTemplate' => 'mimes'])
            ->assertSee('Шаблон должен быть файлом Word в формате .docx.');
    }

    public function test_real_docx_is_saved_and_redirects_to_list(): void
    {
        $this->actingAsEditor();
        Storage::fake();

        Livewire::test('pages::templates-upload')
            ->set('newTemplate', $this->makeDocxUpload('wizard-template.docx'))
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.templates'));

        $this->assertDatabaseHas('templates', [
            'name' => 'wizard-template.docx',
        ]);

        $path = DB::table('templates')->where('name', 'wizard-template.docx')->value('path');
        $this->assertNotEmpty($path);
        Storage::assertExists($path);
    }

    private function actingAsEditor(): User
    {
        $role = Role::findByName('manager');
        $role->syncPermissions([
            'read report templates',
            'edit report templates',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function makeDocxUpload(string $name): UploadedFile
    {
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText('{{project_name}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        $content = file_get_contents($tmp);
        unlink($tmp);

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
