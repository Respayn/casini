<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats;
use Src\Infrastructure\Reports\YandexMetrikaSearchVisits;

class YandexMetrikaSearchVisitsTest extends TestCase
{
    public function test_groups_yandex_google_and_other_engines(): void
    {
        $totals = YandexMetrikaSearchVisits::totals([
            $this->stats('yandex', 10),
            $this->stats('google', 4),
            $this->stats('mail', 3),
            $this->stats('bing', 1),
        ]);

        $this->assertSame(10, $totals['yandex']);
        $this->assertSame(4, $totals['google']);
        $this->assertSame(4, $totals['other']);
        $this->assertSame(18, $totals['total']);
        $this->assertSame(
            [
                'ym.search_visits.yandex' => '10',
                'ym.search_visits.google' => '4',
                'ym.search_visits.other' => '4',
                'ym.search_visits.total' => '18',
                'ym.search_visits.plan' => '0',
                'ym.search_visits.fact' => '18',
                'ym.search_visits.performance' => '',
            ],
            YandexMetrikaSearchVisits::tagValues([
                $this->stats('yandex', 10),
                $this->stats('google', 4),
                $this->stats('mail', 3),
                $this->stats('bing', 1),
            ]),
        );
    }

    public function test_skips_zero_visits_and_sums_months(): void
    {
        $totals = YandexMetrikaSearchVisits::totals([
            $this->stats('yandex', 10, '2026-09-01'),
            $this->stats('yandex', 5, '2026-10-01'),
            $this->stats('yandex', 0, '2026-08-01'),
            $this->stats('google', 0),
        ]);

        $this->assertSame(15, $totals['yandex']);
        $this->assertSame(0, $totals['google']);
        $this->assertSame(0, $totals['other']);
        $this->assertSame(15, $totals['total']);
    }

    public function test_empty_stats_give_zero(): void
    {
        $this->assertSame(
            [
                'yandex' => 0,
                'google' => 0,
                'other' => 0,
                'total' => 0,
            ],
            YandexMetrikaSearchVisits::totals([]),
        );
        $this->assertSame('0', YandexMetrikaSearchVisits::tagValues([])['ym.search_visits.total']);
    }

    public function test_plan_fact_and_performance(): void
    {
        $values = YandexMetrikaSearchVisits::tagValues([
            $this->stats('yandex', 70),
        ], 100);

        $this->assertSame('100', $values['ym.search_visits.plan']);
        $this->assertSame('70', $values['ym.search_visits.fact']);
        $this->assertSame('70%', $values['ym.search_visits.performance']);
        $this->assertSame('70%', YandexMetrikaSearchVisits::performancePercent(70, 100));
    }

    public function test_zero_plan_gives_empty_performance(): void
    {
        $values = YandexMetrikaSearchVisits::tagValues([
            $this->stats('yandex', 70),
        ], 0);

        $this->assertSame('0', $values['ym.search_visits.plan']);
        $this->assertSame('70', $values['ym.search_visits.fact']);
        $this->assertSame('', $values['ym.search_visits.performance']);
        $this->assertSame('', YandexMetrikaSearchVisits::performancePercent(70, 0));
    }

    private function stats(string $engine, int $visits, string $month = '2026-09-01'): YandexMetrikaSearchEnginesStats
    {
        return YandexMetrikaSearchEnginesStats::restore([
            'project_id' => 1,
            'search_engine' => $engine,
            'month' => $month,
            'visits' => $visits,
            'conversions' => 0,
        ]);
    }
}
