<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\YandexMetrikaSearchEngineGoal;
use Src\Infrastructure\Reports\YandexMetrikaSearchEngineGoals;

class YandexMetrikaSearchEngineGoalsTest extends TestCase
{
    public function test_same_goal_over_two_months_is_one_row_with_total(): void
    {
        $stats = [
            $this->goal('yandex', 1, 'Заявка', 10, '2026-09-01'),
            $this->goal('yandex', 1, 'Заявка', 5, '2026-10-01'),
            $this->goal('yandex', 2, 'Звонок', 0, '2026-09-01'),
            $this->goal('google', 1, 'Заявка', 4, '2026-09-01'),
            $this->goal('yandex', 3, 'Старая', 100, '2026-09-01'),
        ];

        $rows = YandexMetrikaSearchEngineGoals::rows($stats, 'yandex', [1, 2]);

        $this->assertSame(
            [
                ['name' => 'Заявка', 'value' => 15],
                ['name' => 'Звонок', 'value' => 0],
            ],
            $rows,
        );
        $this->assertSame(15, YandexMetrikaSearchEngineGoals::total($stats, 'yandex', [1, 2]));
        $this->assertSame(
            [
                ['phrase' => 'Заявка', 'position' => '15'],
                ['phrase' => 'Звонок', 'position' => '0'],
            ],
            YandexMetrikaSearchEngineGoals::pairRows($stats, 'yandex', [1, 2]),
        );
        $this->assertSame(
            [['name' => 'Заявка', 'value' => 4]],
            YandexMetrikaSearchEngineGoals::rows($stats, 'google', [1]),
        );
    }

    public function test_selected_goal_without_rows_stays_zero(): void
    {
        $this->assertSame(
            [['name' => 'Цель 9', 'value' => 0]],
            YandexMetrikaSearchEngineGoals::rows([], 'yandex', [9]),
        );
        $this->assertSame([], YandexMetrikaSearchEngineGoals::rows([], 'yandex', []));
        $this->assertTrue(YandexMetrikaSearchEngineGoals::isPairTag('ym.goals_yandex.name#1'));
        $this->assertTrue(YandexMetrikaSearchEngineGoals::isPairTag('ym.goals_google.value'));
        $this->assertFalse(YandexMetrikaSearchEngineGoals::isPairTag('ym.goals_yandex.total'));
        $this->assertFalse(YandexMetrikaSearchEngineGoals::isPairTag('ym.table.goals_search_engines.yandex'));
    }

    private function goal(
        string $engine,
        int $goalId,
        string $name,
        int $conversions,
        string $month = '2026-09-01',
    ): YandexMetrikaSearchEngineGoal {
        return YandexMetrikaSearchEngineGoal::restore([
            'project_id' => 1,
            'search_engine' => $engine,
            'goal_id' => $goalId,
            'goal_name' => $name,
            'month' => $month,
            'conversions' => $conversions,
        ]);
    }
}
