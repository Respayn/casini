<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\YandexMetrikaVisitsSearchQueries;
use Src\Infrastructure\Reports\YandexMetrikaSearchQueries;

class YandexMetrikaSearchQueriesTest extends TestCase
{
    public function test_same_phrase_over_two_months_is_one_row(): void
    {
        $rows = YandexMetrikaSearchQueries::rows([
            $this->query('купить диван', visits: 10, visitors: 3, month: '2026-09-01'),
            $this->query('купить диван', visits: 5, visitors: 2, month: '2026-10-01'),
            $this->query('шкаф', visits: 4, visitors: 20, month: '2026-09-01'),
        ]);

        $this->assertSame(
            [
                ['phrase' => 'купить диван', 'visits' => 15],
                ['phrase' => 'шкаф', 'visits' => 4],
            ],
            $rows,
        );
        $this->assertSame(
            [
                ['phrase' => 'купить диван', 'position' => '15'],
                ['phrase' => 'шкаф', 'position' => '4'],
            ],
            YandexMetrikaSearchQueries::pairRows([
                $this->query('купить диван', visits: 10, visitors: 3, month: '2026-09-01'),
                $this->query('купить диван', visits: 5, visitors: 2, month: '2026-10-01'),
                $this->query('шкаф', visits: 4, visitors: 20, month: '2026-09-01'),
            ]),
        );
    }

    public function test_users_metric_reads_visitors(): void
    {
        $rows = YandexMetrikaSearchQueries::rows([
            $this->query('купить диван', visits: 10, visitors: 3),
            $this->query('шкаф', visits: 4, visitors: 20),
            $this->query('стул', visits: 8, visitors: 0),
        ], true);

        $this->assertSame(
            [
                ['phrase' => 'шкаф', 'visits' => 20],
                ['phrase' => 'купить диван', 'visits' => 3],
            ],
            $rows,
        );
    }

    public function test_empty_and_zero_rows_are_skipped(): void
    {
        $this->assertSame([], YandexMetrikaSearchQueries::rows([]));
        $this->assertSame([], YandexMetrikaSearchQueries::rows([
            $this->query('', visits: 10),
            $this->query('стул', visits: 0, visitors: 5),
        ]));
        $this->assertTrue(YandexMetrikaSearchQueries::isTag('ym.search_queries.visits#1'));
        $this->assertFalse(YandexMetrikaSearchQueries::isTag('ym.search_visits.total'));
    }

    private function query(
        string $phrase,
        int $visits = 0,
        int $visitors = 0,
        string $month = '2026-09-01',
    ): YandexMetrikaVisitsSearchQueries {
        return YandexMetrikaVisitsSearchQueries::restore([
            'project_id' => 1,
            'month' => $month,
            'phrase' => $phrase,
            'visits' => $visits,
            'visitors' => $visitors,
            'goal_reaches' => 0,
        ]);
    }
}
