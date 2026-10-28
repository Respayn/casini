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

    public function test_keeps_search_phrase_and_position_on_the_same_row(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('{{yandex_search.phrase[1]}}');
        $row->addCell()->addText('{{yandex_search.position[1]}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/search-pair.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/search-pair.docx',
            ReportData::builder()
                ->keywordPositionPair(
                    'yandex_search.phrase[1]',
                    'yandex_search.position[1]',
                    [
                        ['phrase' => 'диван', 'position' => '3'],
                        ['phrase' => 'шкаф', 'position' => '10'],
                    ],
                )
                ->build(),
            'search-pair-report'
        );

        $rows = $this->tableRowTexts($generated->path);
        $sofaRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'диван')));
        $wardrobeRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'шкаф')));

        $this->assertCount(1, $sofaRows);
        $this->assertStringContainsString('3', $sofaRows[0]);
        $this->assertStringNotContainsString('10', $sofaRows[0]);
        $this->assertCount(1, $wardrobeRows);
        $this->assertStringContainsString('10', $wardrobeRows[0]);
        $this->assertStringNotContainsString('диван', $wardrobeRows[0]);
        $this->assertStringNotContainsString('{{', implode("\n", $rows));
    }

    public function test_clears_search_position_when_it_is_not_in_the_phrase_row(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $phraseTable = $section->addTable();
        $phraseTable->addRow()->addCell()->addText('{{yandex_search.phrase[1]}}');
        $positionTable = $section->addTable();
        $positionTable->addRow()->addCell()->addText('{{yandex_search.position[1]}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/search-split-pair.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/search-split-pair.docx',
            ReportData::builder()
                ->keywordPositionPair(
                    'yandex_search.phrase[1]',
                    'yandex_search.position[1]',
                    [
                        ['phrase' => 'диван', 'position' => '3'],
                        ['phrase' => 'шкаф', 'position' => '10'],
                    ],
                )
                ->build(),
            'search-split-pair-report'
        );

        $xml = $this->documentText($generated->path);
        $this->assertStringContainsString('диван', $xml);
        $this->assertStringContainsString('шкаф', $xml);
        $this->assertStringNotContainsString('{{', $xml);
        $this->assertStringNotContainsString('yandex_search.position', $xml);
        $rows = $this->tableRowTexts($generated->path);
        foreach ($rows as $row) {
            $this->assertFalse(str_contains($row, 'диван') && str_contains($row, '3'));
            $this->assertFalse(str_contains($row, 'шкаф') && str_contains($row, '10'));
        }
    }

    public function test_keeps_search_query_phrase_and_visits_on_the_same_row(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('{{ym.search_queries.phrase}}');
        $row->addCell()->addText('{{ym.search_queries.visits}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/search-queries-pair.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/search-queries-pair.docx',
            ReportData::builder()
                ->keywordPositionPair(
                    'ym.search_queries.phrase',
                    'ym.search_queries.visits',
                    [
                        ['phrase' => 'купить диван', 'position' => '15'],
                        ['phrase' => 'шкаф', 'position' => '4'],
                    ],
                )
                ->build(),
            'search-queries-pair-report'
        );

        $rows = $this->tableRowTexts($generated->path);
        $sofaRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'купить диван')));
        $wardrobeRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'шкаф')));

        $this->assertCount(1, $sofaRows);
        $this->assertStringContainsString('15', $sofaRows[0]);
        $this->assertStringNotContainsString('4', $sofaRows[0]);
        $this->assertCount(1, $wardrobeRows);
        $this->assertStringContainsString('4', $wardrobeRows[0]);
        $this->assertStringNotContainsString('купить диван', $wardrobeRows[0]);
        $this->assertStringNotContainsString('{{', implode("\n", $rows));
    }

    public function test_keeps_geo_city_and_visits_on_the_same_row(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('{{ym.visits_geo.city}}');
        $row->addCell()->addText('{{ym.visits_geo.visits}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/geo-pair.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/geo-pair.docx',
            ReportData::builder()
                ->keywordPositionPair(
                    'ym.visits_geo.city',
                    'ym.visits_geo.visits',
                    [
                        ['phrase' => 'Челябинск', 'position' => '15'],
                        ['phrase' => 'Екатеринбург', 'position' => '4'],
                    ],
                )
                ->build(),
            'geo-pair-report'
        );

        $rows = $this->tableRowTexts($generated->path);
        $chelyabinskRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'Челябинск')));
        $yekaterinburgRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'Екатеринбург')));

        $this->assertCount(1, $chelyabinskRows);
        $this->assertStringContainsString('15', $chelyabinskRows[0]);
        $this->assertStringNotContainsString('4', $chelyabinskRows[0]);
        $this->assertCount(1, $yekaterinburgRows);
        $this->assertStringContainsString('4', $yekaterinburgRows[0]);
        $this->assertStringNotContainsString('Челябинск', $yekaterinburgRows[0]);
        $this->assertStringNotContainsString('{{', implode("\n", $rows));
    }

    public function test_keeps_yandex_goal_name_and_value_on_the_same_row(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('{{ym.goals_yandex.name}}');
        $row->addCell()->addText('{{ym.goals_yandex.value}}');
        $section->addText('{{ym.goals_yandex.total}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/goals-pair.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/goals-pair.docx',
            ReportData::builder()
                ->keywordPositionPair(
                    'ym.goals_yandex.name',
                    'ym.goals_yandex.value',
                    [
                        ['phrase' => 'Заявка', 'position' => '15'],
                        ['phrase' => 'Звонок', 'position' => '0'],
                    ],
                )
                ->value('ym.goals_yandex.total', '15')
                ->build(),
            'goals-pair-report'
        );

        $rows = $this->tableRowTexts($generated->path);
        $leadRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'Заявка')));
        $callRows = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'Звонок')));

        $this->assertCount(1, $leadRows);
        $this->assertStringContainsString('15', $leadRows[0]);
        $this->assertStringNotContainsString('Звонок', $leadRows[0]);
        $this->assertCount(1, $callRows);
        $this->assertStringContainsString('0', $callRows[0]);
        $this->assertStringNotContainsString('Заявка', $callRows[0]);
        $this->assertStringNotContainsString('{{', implode("\n", $rows));
    }

    public function test_fills_worklist_in_paragraph_after_table(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addTable()->addRow()->addCell()->addText('{{queries}}');
        $section->addListItem('{{bitrix24.worklist}}', 0);

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/worklist-paragraph.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/worklist-paragraph.docx',
            ReportData::builder()
                ->list('queries', ['купить слона'])
                ->list('bitrix24.worklist', [
                    'Проверены бэкапы',
                    "Строка 1\nСтрока 2",
                ])
                ->build(),
            'worklist-paragraph-report'
        );

        $xml = $this->documentXml($generated->path);
        $this->assertStringContainsString('купить слона', $xml);
        $this->assertStringContainsString('Проверены бэкапы', $xml);
        $this->assertStringContainsString('Строка 1', $xml);
        $this->assertStringContainsString('<w:br/>', $xml);
        $this->assertStringContainsString('Строка 2', $xml);
        $this->assertStringNotContainsString('{{bitrix24.worklist}}', $xml);
        $this->assertGreaterThanOrEqual(2, substr_count($xml, 'Проверены бэкапы') + substr_count($xml, 'Строка 1'));
    }

    public function test_filters_search_queries_by_min_visits_tag(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('{{ym.search_queries.phrase}}');
        $row->addCell()->addText('{{ym.search_queries.visits[10]}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/search-queries-min.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/search-queries-min.docx',
            ReportData::builder()
                ->keywordPositionPair(
                    'ym.search_queries.phrase',
                    'ym.search_queries.visits',
                    [
                        ['phrase' => 'ммк метиз', 'position' => '351'],
                        ['phrase' => 'шкаф', 'position' => '4'],
                    ],
                )
                ->build(),
            'search-queries-min-report'
        );

        $rows = $this->tableRowTexts($generated->path);
        $this->assertNotEmpty($rows);
        $matched = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'ммк метиз') && str_contains($row, '351')));
        $this->assertCount(1, $matched);
        $this->assertSame([], array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'шкаф'))));
        $this->assertStringNotContainsString('{{', implode("\n", $rows));
    }

    public function test_filters_geo_visits_by_min_visits_tag(): void
    {
        Storage::fake();

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('{{ym.visits_geo.city}}');
        $row->addCell()->addText('{{ym.visits_geo.visits[10]}}');

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::put('templates/geo-min.docx', file_get_contents($tmp));
        unlink($tmp);

        $generated = (new ReportGenerator)->generate(
            'templates/geo-min.docx',
            ReportData::builder()
                ->keywordPositionPair(
                    'ym.visits_geo.city',
                    'ym.visits_geo.visits',
                    [
                        ['phrase' => 'Москва', 'position' => '798'],
                        ['phrase' => 'Курган', 'position' => '3'],
                    ],
                )
                ->build(),
            'geo-min-report'
        );

        $rows = $this->tableRowTexts($generated->path);
        $matched = array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'Москва') && str_contains($row, '798')));
        $this->assertCount(1, $matched);
        $this->assertSame([], array_values(array_filter($rows, static fn (string $row): bool => str_contains($row, 'Курган'))));
        $this->assertStringNotContainsString('{{', implode("\n", $rows));
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

    private function documentXml(string $file): string
    {
        $zip = new ZipArchive;
        $zip->open($file);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return (string) $xml;
    }

    private function documentText(string $file): string
    {
        return strip_tags(str_replace('</w:p>', "</w:p>\n", $this->documentXml($file)));
    }

    /**
     * @return list<string>
     */
    private function tableRowTexts(string $file): array
    {
        $zip = new ZipArchive;
        $zip->open($file);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        preg_match_all('/<w:tr\b.*?<\/w:tr>/s', $xml, $matches);

        return array_values(array_filter(array_map(
            static function (string $row): string {
                $withCellBreaks = str_replace('</w:tc>', ' </w:tc>', $row);

                return trim(preg_replace('/\s+/u', ' ', strip_tags($withCellBreaks)) ?? '');
            },
            $matches[0],
        )));
    }
}
