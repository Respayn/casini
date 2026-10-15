<?php

namespace Tests\Feature\Application\Templates;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Src\Application\Templates\GetList\GetTemplatesListQuery;
use Src\Application\Templates\GetList\GetTemplatesListQueryHandler;
use Src\Application\Templates\GetList\TemplateListItemDto;
use Tests\TestCase;

class GetTemplatesListTagsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    public function test_list_contains_unique_tags_without_block_closers(): void
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Клиент: ${client_name}');
        $section->addText('${seo_block}');
        $section->addText('Текст блока');
        $section->addText('${/seo_block}');
        $section->addText('Еще раз: ${client_name}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/with-tags.docx', file_get_contents($tmp));
        unlink($tmp);

        $id = $this->insertTemplate('templates/with-tags.docx');

        $this->assertSame(['client_name', 'seo_block'], $this->findItem($id)->tags);
    }

    public function test_missing_file_gives_null_tags(): void
    {
        $id = $this->insertTemplate('templates/missing.docx');

        $this->assertNull($this->findItem($id)->tags);
    }

    private function insertTemplate(string $path): int
    {
        return DB::table('templates')->insertGetId([
            'name' => basename($path),
            'path' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findItem(int $id): TemplateListItemDto
    {
        $items = app(GetTemplatesListQueryHandler::class)->handle(new GetTemplatesListQuery);

        foreach ($items as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        $this->fail('Шаблон '.$id.' не найден в списке');
    }
}
