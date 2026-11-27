<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Infrastructure\Reports\KeywordPairThreshold;

class KeywordPairThresholdTest extends TestCase
{
    public function test_resolve_reads_min_from_visits_tag(): void
    {
        $resolved = KeywordPairThreshold::resolve(
            'ym.search_queries.phrase',
            'ym.search_queries.visits',
            [
                'ym.search_queries.phrase',
                'ym.search_queries.visits[10]',
            ],
        );

        $this->assertSame('ym.search_queries.phrase', $resolved['phraseKey']);
        $this->assertSame('ym.search_queries.visits[10]', $resolved['positionKey']);
        $this->assertSame(10, $resolved['min']);
    }

    public function test_filter_rows_keeps_values_from_min(): void
    {
        $rows = KeywordPairThreshold::filterRows([
            ['phrase' => 'ммк метиз', 'position' => '351'],
            ['phrase' => 'шкаф', 'position' => '4'],
            ['phrase' => 'канат', 'position' => '-'],
        ], 10);

        $this->assertSame([
            ['phrase' => 'ммк метиз', 'position' => '351'],
        ], $rows);
    }

    public function test_without_brackets_keeps_all_rows(): void
    {
        $resolved = KeywordPairThreshold::resolve(
            'ym.visits_geo.city',
            'ym.visits_geo.visits',
            ['ym.visits_geo.city', 'ym.visits_geo.visits'],
        );

        $this->assertNull($resolved['min']);
        $this->assertCount(2, KeywordPairThreshold::filterRows([
            ['phrase' => 'Москва', 'position' => '15'],
            ['phrase' => 'Курган', 'position' => '4'],
        ], $resolved['min']));
    }
}
