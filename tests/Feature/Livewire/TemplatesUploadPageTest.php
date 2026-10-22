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
            ->assertSee('{{yandex_search.region[1]}}')
            ->assertSee('{{yandex_search.phrase[1]}}')
            ->assertSee('{{yandex_search.position[1]}}')
            ->assertSee('{{yandex_search.top_10[1]}}')
            ->assertSee('Название региона. [1] первый в списке настроек интеграции, [2] второй')
            ->assertSee('Доля фраз из этой таблицы в топ-10, например 70%. Знак процента уже в значении, ячейку ставят отдельно от строки с фразой. [1] первый в списке настроек интеграции, [2] второй')
            ->assertSee('Поисковая фраза. Ставят в одну строку таблицы Word рядом с позицией. [1] первый в списке настроек интеграции, [2] второй')
            ->assertSee('Позиция этой фразы. Ставят в ту же строку, что и фразу. [1] первый в списке настроек интеграции, [2] второй')
            ->assertDontSee('{{yandex_search.region}}')
            ->assertDontSee('{{yandex_search.table}}')
            ->assertDontSee('{{yandex_search.table[1]}}')
            ->assertSee('{{bitrix24.worklist}}')
            ->assertDontSee('{{megaplan.list}}')
            ->assertDontSee('Популярные теги')
            ->assertDontSee('Весь список тегов')
            ->assertDontSee('Категория')
            ->assertSee('Тег')
            ->assertSee('Что подставляет')
            ->assertSee('Текущий год')
            ->assertSee('Начало периода цифрами, например 01.09.2026')
            ->assertSee('Конец периода цифрами, например 30.09.2026')
            ->assertSee('{{period_from_text}}')
            ->assertSee('{{period_to_text}}')
            ->assertSee('Начало периода текстом, например 1 сентября 2026')
            ->assertSee('Конец периода текстом, например 30 сентября 2026')
            ->assertSee('Закрытые пункты чек-листа задачи месяца и комментарии со спецсимволом 💪')
            ->assertSee('Формат отображения')
            ->assertSee('текст')
            ->assertSee('цифры')
            ->assertSee('изображение')
            ->assertSee('таблица')
            ->assertSeeHtml('title="Источник: Касини"')
            ->assertSeeHtml('title="Источник: Яндекс Директ"')
            ->assertSeeHtml('title="Источник: Битрикс24"')
            ->assertSeeHtml('title="Скопировать"')
            ->assertSeeHtml('aria-label="Скопировать"')
            ->assertSeeHtml('data-copy="{{project_name}}"')
            ->assertDontSee('@js')
            ->set('step', 2)
            ->assertSee('Название шаблона')
            ->assertSee('Файл шаблона')
            ->assertSee('Выбрать файл')
            ->assertSee('Сохранить шаблон')
            ->assertSeeHtml('accept=".docx"')
            ->assertSeeHtml('class="sr-only"')
            ->assertDontSee('Файл не выбран');
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
            ->set('step', 2)
            ->set('newTemplate', UploadedFile::fake()->create('template.pdf', 10, 'application/pdf'))
            ->assertHasErrors(['newTemplate' => 'mimes'])
            ->assertSee('Шаблон должен быть файлом Word в формате .docx.');
    }

    public function test_save_button_is_disabled_until_name_and_file_are_set(): void
    {
        $this->actingAsEditor();

        $page = Livewire::test('pages::templates-upload')
            ->set('step', 2);

        $this->assertFalse($page->instance()->canSaveTemplate);

        $page->set('templateName', 'Шаблон');
        $this->assertFalse($page->instance()->canSaveTemplate);

        $page->set('newTemplate', $this->makeDocxUpload('wizard-template.docx'));
        $this->assertTrue($page->instance()->canSaveTemplate);
    }

    public function test_save_without_file_shows_required_error(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->set('step', 2)
            ->set('templateName', 'Шаблон без файла')
            ->call('saveTemplate')
            ->assertHasErrors(['newTemplate' => 'required'])
            ->assertSee('Выберите файл шаблона.');
    }

    public function test_save_without_name_shows_required_error(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->set('step', 2)
            ->set('newTemplate', $this->makeDocxUpload('wizard-template.docx'))
            ->call('saveTemplate')
            ->assertHasErrors(['templateName' => 'required'])
            ->assertSee('Укажите название шаблона.');
    }

    public function test_typing_name_clears_name_error(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->set('step', 2)
            ->call('saveTemplate')
            ->assertHasErrors(['templateName' => 'required'])
            ->set('templateName', 'Ежемесячный отчёт SEO')
            ->assertHasNoErrors('templateName');
    }

    public function test_template_name_keeps_spaces_while_typing(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->set('step', 2)
            ->set('templateName', 'Тут шаблона по SEO ')
            ->assertSet('templateName', 'Тут шаблона по SEO ');
    }

    public function test_opening_step_two_does_not_show_errors(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->call('goToStepTwo')
            ->assertHasNoErrors()
            ->assertDontSee('Укажите название шаблона.')
            ->assertDontSee('Выберите файл шаблона.');
    }

    public function test_delete_clears_selected_file(): void
    {
        $this->actingAsEditor();

        Livewire::test('pages::templates-upload')
            ->set('step', 2)
            ->set('newTemplate', $this->makeDocxUpload('wizard-template.docx'))
            ->assertSee('wizard-template.docx')
            ->call('deleteTemplateFile')
            ->assertSet('newTemplate', null)
            ->assertSee('Выбрать файл');
    }

    public function test_edit_opens_step_two_with_loaded_template(): void
    {
        $this->actingAsEditor();

        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Ежемесячный отчёт',
            'path' => 'templates/existing.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = Livewire::test('pages::templates-upload', ['templateId' => $templateId]);

        $page
            ->assertSet('step', 2)
            ->assertSet('editingTemplateId', $templateId)
            ->assertSet('templateName', 'Ежемесячный отчёт')
            ->assertSee('Редактирование шаблона')
            ->assertDontSee('Создание шаблона')
            ->assertSee('Ежемесячный отчёт.docx')
            ->assertSee('Сохранить шаблон');

        $this->assertTrue($page->instance()->canSaveTemplate);
    }

    public function test_edit_saves_new_name_without_replacing_file(): void
    {
        $this->actingAsEditor();
        Storage::fake();
        Storage::put('templates/existing.docx', 'old');

        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Старое название',
            'path' => 'templates/existing.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test('pages::templates-upload', ['templateId' => $templateId])
            ->set('templateName', 'Новое название')
            ->call('saveTemplate')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.templates'));

        $this->assertDatabaseHas('templates', [
            'id' => $templateId,
            'name' => 'Новое название',
            'path' => 'templates/existing.docx',
        ]);
        $this->assertDatabaseMissing('templates', [
            'name' => 'Старое название',
        ]);
        Storage::assertExists('templates/existing.docx');
    }

    public function test_edit_replaces_file_when_new_docx_uploaded(): void
    {
        $this->actingAsEditor();
        Storage::fake();
        Storage::put('templates/existing.docx', 'old');

        $templateId = DB::table('templates')->insertGetId([
            'name' => 'Шаблон для замены',
            'path' => 'templates/existing.docx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test('pages::templates-upload', ['templateId' => $templateId])
            ->set('newTemplate', $this->makeDocxUpload('replacement.docx'))
            ->call('saveTemplate')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.templates'));

        $path = DB::table('templates')->where('id', $templateId)->value('path');
        $this->assertNotSame('templates/existing.docx', $path);
        $this->assertNotEmpty($path);
        Storage::assertExists($path);
        Storage::assertMissing('templates/existing.docx');
        $this->assertDatabaseHas('templates', [
            'id' => $templateId,
            'name' => 'Шаблон для замены',
        ]);
    }

    public function test_real_docx_is_saved_and_redirects_to_list(): void
    {
        $this->actingAsEditor();
        Storage::fake();

        Livewire::test('pages::templates-upload')
            ->set('step', 2)
            ->set('templateName', 'Шаблон для отчёта')
            ->set('newTemplate', $this->makeDocxUpload('wizard-template.docx'))
            ->assertHasNoErrors()
            ->assertSee('wizard-template.docx')
            ->assertSee('Удалить файл шаблона')
            ->call('saveTemplate')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.templates'));

        $this->assertDatabaseHas('templates', [
            'name' => 'Шаблон для отчёта',
        ]);

        $path = DB::table('templates')->where('name', 'Шаблон для отчёта')->value('path');
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
