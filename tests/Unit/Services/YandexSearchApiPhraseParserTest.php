<?php

namespace Tests\Unit\Services;

use App\Services\YandexSearchApiPhraseParser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class YandexSearchApiPhraseParserTest extends TestCase
{
    #[Test]
    public function test_parses_phrases_line_by_line_from_docx(): void
    {
        $path = $this->createDocxWithLines([
            'болт высокопрочный цена',
            'болт гост 7798 70',
        ]);

        $phrases = app(YandexSearchApiPhraseParser::class)->parseFromPath($path);

        $this->assertSame([
            'болт высокопрочный цена',
            'болт гост 7798 70',
        ], $phrases);
    }

    #[Test]
    public function test_skips_empty_lines(): void
    {
        $path = $this->createDocxWithLines([
            'фраза 1',
            '',
            '   ',
            'фраза 2',
        ]);

        $phrases = app(YandexSearchApiPhraseParser::class)->parseFromPath($path);

        $this->assertSame(['фраза 1', 'фраза 2'], $phrases);
    }

    #[Test]
    public function test_throws_for_invalid_file(): void
    {
        $this->expectException(RuntimeException::class);

        app(YandexSearchApiPhraseParser::class)->parseFromPath('/tmp/non-existent-file.docx');
    }

    #[Test]
    public function test_parses_text_run_with_multiple_text_fragments_without_duplicates(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $textRun = $section->addTextRun();
        $textRun->addText('Агентство ');
        $textRun->addText('seo');

        $section->addText('Агентство контекстной рекламы');

        $path = sys_get_temp_dir().'/yandex-search-api-test-'.uniqid().'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $phrases = app(YandexSearchApiPhraseParser::class)->parseFromPath($path);

        $this->assertSame([
            'Агентство seo',
            'Агентство контекстной рекламы',
        ], $phrases);
    }

    /**
     * @param  string[]  $lines
     */
    private function createDocxWithLines(array $lines): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText(implode("\n", $lines));

        $path = sys_get_temp_dir().'/yandex-search-api-test-'.uniqid().'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }
}
