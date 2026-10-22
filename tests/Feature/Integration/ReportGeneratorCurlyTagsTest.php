<?php

namespace Tests\Feature\Integration;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Src\Application\Reports\Generate\ReportData;
use Src\Infrastructure\Reports\ReportGenerator;
use Src\Infrastructure\Templates\PhpWordTemplateTagReader;
use Src\Infrastructure\Templates\ReportTemplateProcessor;
use Tests\TestCase;
use ZipArchive;

class ReportGeneratorCurlyTagsTest extends TestCase
{
    public function test_reads_tags_when_word_splits_each_brace_into_own_run(): void
    {
        Storage::fake();

        $path = $this->storeDocxWithSplitBraces([
            'current_year',
            'callibri.table',
        ]);

        $tags = (new PhpWordTemplateTagReader)->read($path);

        $this->assertSame(['current_year', 'callibri.table'], $tags);

        $generated = (new ReportGenerator)->generate(
            $path,
            ReportData::builder()->value('current_year', '2026')->value('callibri.table', 'таблица')->build(),
            'split-braces-report'
        );

        $xml = $this->documentText($generated->path);
        $this->assertStringContainsString('2026', $xml);
        $this->assertStringContainsString('таблица', $xml);
        $this->assertStringNotContainsString('{{', $xml);
    }

    public function test_fills_curly_tags_in_values_lists_blocks_and_tables(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Клиент: {{client_name}}');
        $brokenRun = $section->addTextRun();
        $brokenRun->addText('Год: {{current_');
        $brokenRun->addText('year}}', ['bold' => true]);
        $section->addText('{{shown_block}}');
        $section->addText('Видимый блок');
        $section->addText('{{/shown_block}}');
        $section->addText('{{hidden_block}}');
        $section->addText('Скрытый блок');
        $section->addText('{{/hidden_block}}');
        $section->addTable()->addRow()->addCell()->addText('{{queries}}');
        $section->addText('{{positions}}');
        $section->addText('Старый формат: ${client_name}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/curly.docx', file_get_contents($tmp));
        unlink($tmp);

        $data = ReportData::builder()
            ->value('client_name', 'ООО Ромашка')
            ->value('current_year', '2026')
            ->condition('shown_block', true)
            ->condition('hidden_block', false)
            ->list('queries', ['купить слона', 'слон недорого'])
            ->table('positions', ['Запрос', 'Позиция'], [['купить слона', '3']])
            ->build();

        $generated = (new ReportGenerator)->generate('templates/curly.docx', $data, 'curly-report');
        $xml = $this->documentText($generated->path);

        $this->assertStringContainsString('Клиент: ООО Ромашка', $xml);
        $this->assertStringContainsString('Год: 2026', $xml);
        $this->assertStringContainsString('Видимый блок', $xml);
        $this->assertStringNotContainsString('Скрытый блок', $xml);
        $this->assertStringContainsString('купить слона', $xml);
        $this->assertStringContainsString('слон недорого', $xml);
        $this->assertStringContainsString('Позиция', $xml);
        $this->assertStringContainsString('Старый формат: ${client_name}', $xml);
        $this->assertStringNotContainsString('{{', $xml);
    }

    public function test_clears_unindexed_and_missing_yandex_search_tags(): void
    {
        Storage::fake();

        $path = $this->storeDocxWithSplitBraces([
            'yandex_search.region[1]',
            'yandex_search.region[3]',
            'yandex_search.table',
            'yandex_search.top_3',
            'current_year',
        ]);

        $generated = (new ReportGenerator)->generate(
            $path,
            ReportData::builder()
                ->value('current_year', '2026')
                ->value('yandex_search.region[1]', 'Москва')
                ->build(),
            'search-index-report'
        );

        $xml = $this->documentText($generated->path);
        $this->assertStringContainsString('Москва', $xml);
        $this->assertStringContainsString('2026', $xml);
        $this->assertStringNotContainsString('{{', $xml);
        $this->assertStringNotContainsString('yandex_search.region[3]', $xml);
        $this->assertStringNotContainsString('yandex_search.table', $xml);
        $this->assertStringNotContainsString('yandex_search.top_3', $xml);
    }

    /**
     * @param  list<string>  $tagNames
     */
    private function storeDocxWithSplitBraces(array $tagNames): string
    {
        $paragraphs = [];
        foreach ($tagNames as $name) {
            $paragraphs[] = '<w:p>'
                .'<w:r><w:t>{</w:t></w:r>'
                .'<w:r><w:t>{</w:t></w:r>'
                .'<w:r><w:t>'.$name.'</w:t></w:r>'
                .'<w:r><w:t>}</w:t></w:r>'
                .'<w:r><w:t>}</w:t></w:r>'
                .'</w:p>';
        }

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.implode('', $paragraphs).'<w:sectPr/></w:body></w:document>';

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $storagePath = 'templates/split-braces.docx';
        Storage::put($storagePath, file_get_contents($tmp));
        unlink($tmp);

        // sanity: processor must see tags before generate
        $this->assertSame(
            $tagNames,
            (new ReportTemplateProcessor(Storage::path($storagePath)))->getVariables()
        );

        return $storagePath;
    }

    private function documentText(string $file): string
    {
        $zip = new ZipArchive;
        $zip->open($file);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return strip_tags(str_replace('</w:p>', "</w:p>\n", $xml));
    }
}
