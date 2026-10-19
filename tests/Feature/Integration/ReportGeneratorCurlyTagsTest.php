<?php

namespace Tests\Feature\Integration;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Src\Application\Reports\Generate\ReportData;
use Src\Infrastructure\Reports\ReportGenerator;
use Tests\TestCase;
use ZipArchive;

class ReportGeneratorCurlyTagsTest extends TestCase
{
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

        $file = (new ReportGenerator)->generate('templates/curly.docx', $data, 'curly-report');
        $xml = $this->documentText($file);

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

    private function documentText(string $file): string
    {
        $zip = new ZipArchive;
        $zip->open($file);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return strip_tags(str_replace('</w:p>', "</w:p>\n", $xml));
    }
}
