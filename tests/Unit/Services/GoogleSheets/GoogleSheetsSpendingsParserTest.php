<?php

namespace Tests\Unit\Services\GoogleSheets;

use App\Services\GoogleSheets\GoogleSheetsSpendingsParser;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleSheetsSpendingsParserTest extends TestCase
{
    private GoogleSheetsSpendingsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new GoogleSheetsSpendingsParser;
    }

    #[Test]
    public function it_sums_copyrighting_rows_for_selected_month(): void
    {
        $rows = [
            ['Статус оплаты', 'Объем ИТОГО, знак', 'итоговый ценник'],
            ['15.08.2026', '1000', '5000'],
            ['20.09.2026', '2000', '9000'],
            ['10.08.2026', '500', '2500'],
        ];

        $result = $this->parser->parseCopyrightingSheet($rows, Carbon::parse('2026-08-01'));

        $this->assertSame(1500.0, $result['hours']);
        $this->assertSame(7500.0, $result['sum']);
    }

    #[Test]
    public function it_returns_zero_when_no_rows_match_month(): void
    {
        $rows = [
            ['Статус оплаты', 'Объем ИТОГО, знак', 'итоговый ценник'],
            ['15.07.2026', '1000', '5000'],
        ];

        $result = $this->parser->parseCopyrightingSheet($rows, Carbon::parse('2026-08-01'));

        $this->assertSame(0.0, $result['hours']);
        $this->assertSame(0.0, $result['sum']);
    }

    #[Test]
    public function it_reads_seo_links_total_for_selected_month_column(): void
    {
        $rows = [
            ['', 'август 2026', 'сентябрь 2026'],
            ['Проект A', '12000', '8000'],
            ['Итого', '12000', '8000'],
        ];

        $result = $this->parser->parseSeoLinksSheet($rows, Carbon::parse('2026-08-01'));

        $this->assertSame(12000.0, $result);
    }
}
