<?php

namespace Tests\Unit\Services\GoogleSheets;

use App\Services\GoogleSheets\GoogleSheetsSpendingsParser;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleSheetsSpendingsParserTest extends TestCase
{
    private GoogleSheetsSpendingsParser $parser;

    private const PROJECT_URL = 'https://www.example.com/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new GoogleSheetsSpendingsParser;
    }

    #[Test]
    public function it_sums_copyrighting_rows_for_selected_month_and_project_url(): void
    {
        $rows = [
            ['Проект', 'Статус оплаты', 'Объем ИТОГО, знак', 'Итоговый ценник'],
            ['example.com', '15.08.2026', '1000', '5 000 ₽'],
            ['other.ru', '10.08.2026', '500', '2 500'],
            ['https://www.example.com', '10.08.26', '500', '2 500 руб.'],
        ];

        $result = $this->parser->parseCopyrightingSheet(
            $rows,
            Carbon::parse('2026-08-01'),
            self::PROJECT_URL,
        );

        $this->assertSame(1500.0, $result['hours']);
        $this->assertSame(7500.0, $result['sum']);
    }

    #[Test]
    public function it_sums_programming_rows_with_volume_from_obem_itogo_column(): void
    {
        $rows = [
            ['Проект', 'Статус оплаты', 'Объём ИТОГО, час.', 'Итоговый ценник'],
            ['example.com', '15.08.2026', '12', '1 200'],
            ['example.com', '20.09.2026', '8', '800'],
        ];

        $result = $this->parser->parseProgrammingSheet(
            $rows,
            Carbon::parse('2026-08-01'),
            self::PROJECT_URL,
        );

        $this->assertSame(12.0, $result['hours']);
        $this->assertSame(1200.0, $result['sum']);
    }

    #[Test]
    public function it_returns_zero_when_no_rows_match_month(): void
    {
        $rows = [
            ['Проект', 'Статус оплаты', 'Объем ИТОГО, знак', 'Итоговый ценник'],
            ['example.com', '15.07.2026', '1000', '5000'],
        ];

        $result = $this->parser->parseCopyrightingSheet(
            $rows,
            Carbon::parse('2026-08-01'),
            self::PROJECT_URL,
        );

        $this->assertSame(0.0, $result['hours']);
        $this->assertSame(0.0, $result['sum']);
    }

    #[Test]
    public function it_skips_rows_with_invalid_date_or_amount_but_keeps_valid_rows(): void
    {
        $rows = [
            ['Проект', 'Статус оплаты', 'Объем ИТОГО, знак', 'Итоговый ценник'],
            ['example.com', 'не дата', '1000', '1 000 ₽'],
            ['example.com', '15.08.2026', 'abc', ''],
            ['example.com', '15.08.2026', '300', '3 000 ₽'],
        ];

        $result = $this->parser->parseCopyrightingSheet(
            $rows,
            Carbon::parse('2026-08-01'),
            self::PROJECT_URL,
        );

        $this->assertSame(300.0, $result['hours']);
        $this->assertSame(3000.0, $result['sum']);
    }

    #[Test]
    public function it_matches_project_urls_with_and_without_protocol(): void
    {
        $this->assertTrue($this->parser->projectUrlsMatch('https://example.com', 'example.com'));
        $this->assertTrue($this->parser->projectUrlsMatch('example.com/', 'http://www.example.com/path'));
        $this->assertFalse($this->parser->projectUrlsMatch('example.com', 'other.ru'));
    }

    #[Test]
    public function it_throws_when_project_column_is_missing(): void
    {
        $this->expectExceptionMessage('Колонка «Проект» не найдена.');

        $this->parser->parseProgrammingSheet(
            [
                ['Статус оплаты', 'Объем ИТОГО, час', 'Итоговый ценник'],
                ['15.08.2026', '1', '100'],
            ],
            Carbon::parse('2026-08-01'),
            self::PROJECT_URL,
        );
    }
}
