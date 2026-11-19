<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\YandexMetrika\YandexMetrikaSearchEngineGoal;

final class YandexMetrikaSearchEngineGoals
{
    public const YANDEX_NAME_KEY = 'ym.goals_yandex.name';

    public const YANDEX_VALUE_KEY = 'ym.goals_yandex.value';

    public const YANDEX_TOTAL_KEY = 'ym.goals_yandex.total';

    public const GOOGLE_NAME_KEY = 'ym.goals_google.name';

    public const GOOGLE_VALUE_KEY = 'ym.goals_google.value';

    public const GOOGLE_TOTAL_KEY = 'ym.goals_google.total';

    public static function isPairTag(string $variable): bool
    {
        return str_starts_with($variable, self::YANDEX_NAME_KEY)
            || str_starts_with($variable, self::YANDEX_VALUE_KEY)
            || str_starts_with($variable, self::GOOGLE_NAME_KEY)
            || str_starts_with($variable, self::GOOGLE_VALUE_KEY);
    }

    /**
     * @param  list<YandexMetrikaSearchEngineGoal>  $stats
     * @param  list<int>  $selectedGoalIds
     * @return list<array{name: string, value: int}>
     */
    public static function rows(array $stats, string $engine, array $selectedGoalIds): array
    {
        if ($selectedGoalIds === []) {
            return [];
        }

        $selected = array_fill_keys($selectedGoalIds, true);
        $aggregated = [];
        $names = [];

        foreach ($stats as $item) {
            if ($item->getSearchEngine() !== $engine) {
                continue;
            }

            $goalId = $item->getGoalId();
            if (! isset($selected[$goalId])) {
                continue;
            }

            $aggregated[$goalId] = ($aggregated[$goalId] ?? 0) + $item->getConversions();
            $name = trim($item->getGoalName());
            if ($name !== '') {
                $names[$goalId] = $name;
            }
        }

        foreach ($selectedGoalIds as $goalId) {
            if (! isset($aggregated[$goalId])) {
                $aggregated[$goalId] = 0;
            }
            if (! isset($names[$goalId])) {
                $names[$goalId] = 'Цель '.$goalId;
            }
        }

        $rows = [];
        foreach ($aggregated as $goalId => $value) {
            $rows[] = [
                'name' => $names[$goalId],
                'value' => $value,
                'goal_id' => $goalId,
            ];
        }

        usort(
            $rows,
            static function (array $left, array $right): int {
                $byValue = $right['value'] <=> $left['value'];
                if ($byValue !== 0) {
                    return $byValue;
                }

                return strcasecmp($left['name'], $right['name']);
            }
        );

        return array_map(
            static fn (array $row): array => [
                'name' => $row['name'],
                'value' => $row['value'],
            ],
            $rows
        );
    }

    /**
     * @param  list<YandexMetrikaSearchEngineGoal>  $stats
     * @param  list<int>  $selectedGoalIds
     * @return list<array{phrase: string, position: string}>
     */
    public static function pairRows(array $stats, string $engine, array $selectedGoalIds): array
    {
        return array_map(
            static fn (array $row): array => [
                'phrase' => $row['name'],
                'position' => (string) $row['value'],
            ],
            self::rows($stats, $engine, $selectedGoalIds),
        );
    }

    /**
     * @param  list<YandexMetrikaSearchEngineGoal>  $stats
     * @param  list<int>  $selectedGoalIds
     */
    public static function total(array $stats, string $engine, array $selectedGoalIds): int
    {
        return array_sum(array_column(self::rows($stats, $engine, $selectedGoalIds), 'value'));
    }
}
