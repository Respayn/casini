<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\YandexMetrikaVisitsGeo;
use Src\Infrastructure\Reports\YandexMetrikaGeoVisits;

class YandexMetrikaGeoVisitsTest extends TestCase
{
    public function test_same_city_over_two_months_is_one_row(): void
    {
        $rows = YandexMetrikaGeoVisits::rows([
            $this->city('Челябинск', visits: 10, visitors: 3, month: '2026-09-01'),
            $this->city('Челябинск', visits: 5, visitors: 2, month: '2026-10-01'),
            $this->city('Екатеринбург', visits: 4, visitors: 20, month: '2026-09-01'),
        ]);

        $this->assertSame(
            [
                ['city' => 'Челябинск', 'visits' => 15],
                ['city' => 'Екатеринбург', 'visits' => 4],
            ],
            $rows,
        );
        $this->assertSame(
            [
                ['phrase' => 'Челябинск', 'position' => '15'],
                ['phrase' => 'Екатеринбург', 'position' => '4'],
            ],
            YandexMetrikaGeoVisits::pairRows([
                $this->city('Челябинск', visits: 10, visitors: 3, month: '2026-09-01'),
                $this->city('Челябинск', visits: 5, visitors: 2, month: '2026-10-01'),
                $this->city('Екатеринбург', visits: 4, visitors: 20, month: '2026-09-01'),
            ]),
        );
    }

    public function test_users_metric_reads_visitors(): void
    {
        $rows = YandexMetrikaGeoVisits::rows([
            $this->city('Челябинск', visits: 10, visitors: 3),
            $this->city('Екатеринбург', visits: 4, visitors: 20),
            $this->city('Курган', visits: 8, visitors: 0),
        ], true);

        $this->assertSame(
            [
                ['city' => 'Екатеринбург', 'visits' => 20],
                ['city' => 'Челябинск', 'visits' => 3],
            ],
            $rows,
        );
    }

    public function test_empty_and_zero_rows_are_skipped(): void
    {
        $this->assertSame([], YandexMetrikaGeoVisits::rows([]));
        $this->assertSame([], YandexMetrikaGeoVisits::rows([
            $this->city('', visits: 10),
            $this->city('Курган', visits: 0, visitors: 5),
        ]));
        $this->assertTrue(YandexMetrikaGeoVisits::isTag('ym.visits_geo.visits#1'));
        $this->assertFalse(YandexMetrikaGeoVisits::isTag('ym.table.visits_geo'));
        $this->assertFalse(YandexMetrikaGeoVisits::isTag('ym.search_queries.phrase'));
    }

    private function city(
        string $city,
        int $visits = 0,
        int $visitors = 0,
        string $month = '2026-09-01',
    ): YandexMetrikaVisitsGeo {
        return YandexMetrikaVisitsGeo::restore([
            'project_id' => 1,
            'month' => $month,
            'city' => $city,
            'visits' => $visits,
            'visitors' => $visitors,
            'goal_reaches' => 0,
        ]);
    }
}
