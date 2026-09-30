<?php

namespace Tests\Unit\Domain\Statistics;

use App\Data\TableReportRowData;
use App\Domain\Statistics\Services\StatisticsClosingColumnsAggregator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StatisticsClosingColumnsAggregatorTest extends TestCase
{
    private StatisticsClosingColumnsAggregator $aggregator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aggregator = new StatisticsClosingColumnsAggregator;
    }

    public function test_sums_bonuses_amounts_including_guarantees(): void
    {
        $rows = new Collection([
            $this->row(['kind' => 'amount', 'value' => 10000], [], []),
            $this->row(['kind' => 'amount', 'value' => -3000], [], []),
            $this->row(['kind' => 'not_configured'], [], []),
            $this->row(['kind' => 'dash'], [], []),
        ]);

        $result = $this->aggregator->aggregateBonuses($rows);

        $this->assertSame('amount', $result['kind']);
        $this->assertSame(7000.0, $result['value']);
    }

    public function test_summary_totals_use_only_primary_parameters(): void
    {
        $rows = new Collection([
            $this->row(
                ['kind' => 'dash'],
                [
                    ['value' => 100.0, 'format' => 'currency'], // бюджет — не primary
                    ['value' => 10, 'format' => null], // лиды — primary
                ],
                [
                    ['value' => 200.0, 'format' => 'currency'],
                    ['value' => 40, 'format' => null],
                ],
                [
                    ['name' => 'Рекламный бюджет', 'highlight' => false],
                    ['name' => 'Лиды', 'highlight' => true],
                ],
            ),
            $this->row(
                ['kind' => 'dash'],
                [
                    ['value' => 50.0, 'format' => 'currency'],
                    ['value' => 30, 'format' => null],
                ],
                [
                    ['value' => 100.0, 'format' => 'currency'],
                    ['value' => 40, 'format' => null],
                ],
                [
                    ['name' => 'Рекламный бюджет', 'highlight' => false],
                    ['name' => 'Лиды', 'highlight' => true],
                ],
            ),
        ]);

        $summary = $this->aggregator->aggregateSummary($rows);

        // только primary лиды: (10+30)/(40+40)=50%, бюджет в Итого не участвует
        $this->assertCount(1, $summary);
        $this->assertSame(50, $summary[0]['value']);
        $this->assertSame('percent', $summary[0]['format']);
        $this->assertTrue($summary[0]['highlight']);
    }

    public function test_primary_percent_across_different_schemas(): void
    {
        $rows = new Collection([
            $this->row(
                ['kind' => 'dash'],
                [
                    ['value' => 1, 'format' => 'currency'],
                    ['value' => 20, 'format' => null],
                ],
                [
                    ['value' => null, 'format' => 'currency'],
                    ['value' => 40, 'format' => null],
                ],
                [
                    ['name' => 'CPL', 'highlight' => false],
                    ['name' => 'Лиды', 'highlight' => true],
                ],
            ),
            $this->row(
                ['kind' => 'dash'],
                [
                    ['value' => 40, 'format' => null],
                ],
                [
                    ['value' => 80, 'format' => null],
                ],
                [
                    ['name' => 'Объем визитов', 'highlight' => true],
                ],
            ),
        ]);

        $summary = $this->aggregator->aggregateSummary($rows);

        // primary: (20+40)/(40+80)=50%
        $this->assertCount(1, $summary);
        $this->assertSame(50, $summary[0]['value']);
        $this->assertSame('percent', $summary[0]['format']);
    }

    public function test_period_buckets_use_only_primary_parameters(): void
    {
        $rows = new Collection([
            $this->row(
                ['kind' => 'dash'],
                [],
                [],
                [
                    ['name' => 'Рекламный бюджет', 'highlight' => false],
                    ['name' => 'Лиды', 'highlight' => true],
                ],
                [
                    'day_1' => [
                        [
                            'plan' => ['value' => 100, 'format' => 'currency'],
                            'fact' => ['value' => 50, 'format' => 'currency'],
                        ],
                        [
                            'plan' => ['value' => 10, 'format' => null],
                            'fact' => ['value' => 8, 'format' => null],
                        ],
                    ],
                    'day_2' => [
                        [
                            'plan' => ['value' => 100, 'format' => 'currency'],
                            'fact' => ['value' => 20, 'format' => 'currency'],
                        ],
                        [
                            'plan' => ['value' => 10, 'format' => null],
                            'fact' => ['value' => 2, 'format' => null],
                        ],
                    ],
                ],
            ),
            $this->row(
                ['kind' => 'dash'],
                [],
                [],
                [
                    ['name' => 'Рекламный бюджет', 'highlight' => false],
                    ['name' => 'Лиды', 'highlight' => true],
                ],
                [
                    'day_1' => [
                        [
                            'plan' => ['value' => 50, 'format' => 'currency'],
                            'fact' => ['value' => 10, 'format' => 'currency'],
                        ],
                        [
                            'plan' => ['value' => 10, 'format' => null],
                            'fact' => ['value' => 2, 'format' => null],
                        ],
                    ],
                    'day_2' => [
                        [
                            'plan' => ['value' => 50, 'format' => 'currency'],
                            'fact' => ['value' => 5, 'format' => 'currency'],
                        ],
                        [
                            'plan' => ['value' => 10, 'format' => null],
                            'fact' => ['value' => 8, 'format' => null],
                        ],
                    ],
                ],
            ),
        ]);

        $periods = $this->aggregator->aggregatePeriodPercents($rows);

        // day_1 primary лиды: (8+2)/(10+10)=50%
        $this->assertSame(50, $periods['day_1'][0]['value']);
        $this->assertSame('percent', $periods['day_1'][0]['format']);
        // day_2 primary лиды: (2+8)/(10+10)=50%
        $this->assertSame(50, $periods['day_2'][0]['value']);
    }

    /**
     * @param  array{kind: string, value?: float}  $bonuses
     * @param  list<array{value: mixed, format: mixed}>  $summary
     * @param  list<array{value: mixed, format: mixed}>  $plan
     * @param  list<array{name: string, highlight: bool}>  $parameters
     * @param  array<string, list<array{plan: array{value: mixed, format: mixed}, fact: array{value: mixed, format: mixed}}>>  $periodBuckets
     */
    private function row(
        array $bonuses,
        array $summary,
        array $plan,
        array $parameters = [],
        array $periodBuckets = [],
    ): TableReportRowData {
        $row = new TableReportRowData;
        $row->id = 1;
        $row->data = new Collection(array_merge(
            [
                'bonuses' => $bonuses,
                'summary' => $summary,
                'plan' => $plan,
                'parameter' => $parameters,
            ],
            $periodBuckets,
        ));

        return $row;
    }
}
