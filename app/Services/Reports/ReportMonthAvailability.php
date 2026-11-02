<?php

namespace App\Services\Reports;

use App\Models\CallibriDailyLeadCount;
use App\Models\Project;
use App\Models\YandexDirectDailySpending;
use App\Models\YandexSearchApiDailyTopPercent;
use Illuminate\Support\Carbon;

/**
 * Месяцы, в которых у клиенто-проекта заполнена колонка «Итог» в Статистике.
 */
class ReportMonthAvailability
{
    public const UNAVAILABLE_TOOLTIP = 'Нет итога в Статистике. Пересоберите статистику в клиенто-проекте.';

    /**
     * @var list<class-string>
     */
    private const CALCULATOR_CLASSES = [
        'App\\Services\\Statistics\\StatisticsClosingColumnsCalculator',
        'App\\Domain\\Statistics\\Services\\StatisticsClosingColumnsCalculator',
    ];

    /**
     * @return list<string> Месяцы в формате Y-m, от старых к новым
     */
    public function monthsWithSummary(int $projectId, ?Carbon $today = null): array
    {
        $project = Project::query()->find($projectId);
        if ($project === null) {
            return [];
        }

        $today ??= Carbon::now()->startOfDay();
        $calculator = $this->makeCalculator();
        $spendByDay = $this->loadDailyMap(
            YandexDirectDailySpending::query()
                ->where('project_id', $projectId)
                ->get(['date', 'cost_with_vat']),
            'cost_with_vat',
            static fn (mixed $value): float => (float) $value,
        );
        $leadsByDay = $this->loadDailyMap(
            CallibriDailyLeadCount::query()
                ->where('project_id', $projectId)
                ->get(['date', 'leads_count']),
            'leads_count',
            static fn (mixed $value): int => (int) $value,
        );
        $topPercentsByDay = $this->loadDailyMap(
            YandexSearchApiDailyTopPercent::query()
                ->where('project_id', $projectId)
                ->get(['date', 'top_10_percent']),
            'top_10_percent',
            static fn (mixed $value): float => (float) $value,
        );

        $earliest = $this->earliestDay($spendByDay, $leadsByDay, $topPercentsByDay);
        if ($earliest === null) {
            return [];
        }

        $months = [];
        $cursor = $earliest->copy()->startOfMonth()->startOfDay();
        $lastMonth = $today->copy()->startOfMonth()->startOfDay();

        while ($cursor->lte($lastMonth)) {
            $result = $calculator->calculate(
                $project,
                $cursor,
                [],
                $spendByDay,
                $leadsByDay,
                $topPercentsByDay,
                [],
                $today,
            );

            if ($this->summaryHasNumber($result['summary'] ?? [])) {
                $months[] = $cursor->format('Y-m');
            }

            $cursor->addMonth();
        }

        return $months;
    }

    private function makeCalculator(): object
    {
        foreach (self::CALCULATOR_CLASSES as $class) {
            if (class_exists($class)) {
                return app($class);
            }
        }

        throw new \RuntimeException('Не найден калькулятор колонки «Итог» Статистики.');
    }

    /**
     * @param  list<array{value?: mixed}>  $summary
     */
    private function summaryHasNumber(array $summary): bool
    {
        foreach ($summary as $slot) {
            if (is_numeric($slot['value'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, float|int>  $spendByDay
     * @param  array<string, int>  $leadsByDay
     * @param  array<string, float>  $topPercentsByDay
     */
    private function earliestDay(array $spendByDay, array $leadsByDay, array $topPercentsByDay): ?Carbon
    {
        $keys = array_merge(
            array_keys($spendByDay),
            array_keys($leadsByDay),
            array_keys($topPercentsByDay),
        );

        if ($keys === []) {
            return null;
        }

        sort($keys);

        return Carbon::parse($keys[0])->startOfDay();
    }

    /**
     * @param  iterable<int, object>  $rows
     * @param  callable(mixed): (float|int)  $cast
     * @return array<string, float|int>
     */
    private function loadDailyMap(iterable $rows, string $column, callable $cast): array
    {
        $map = [];

        foreach ($rows as $row) {
            $date = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : Carbon::parse((string) $row->date)->toDateString();
            $map[$date] = $cast($row->{$column});
        }

        return $map;
    }
}
