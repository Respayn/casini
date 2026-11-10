<?php

namespace App\Domain\Statistics\Services;

use App\Data\TableReportRowData;
use Illuminate\Support\Collection;

/**
 * Итого по колонкам «Итог», срезам детализации (день/неделя/месяц) и «Бонусы»
 * в рамках группировки / всей таблицы.
 *
 * % в Итого — среднее процентов основных (primary) параметров клиенто-проектов.
 */
class StatisticsClosingColumnsAggregator
{
    /**
     * @param  Collection<int, TableReportRowData>  $rows
     * @return array<string, mixed>
     */
    public function aggregate(Collection $rows, bool $treatMissingPlanAsUnmet = true): array
    {
        return array_merge(
            [
                'summary' => $this->aggregateSummary($rows, $treatMissingPlanAsUnmet),
                'bonuses' => $this->aggregateBonuses($rows),
            ],
            $this->aggregatePeriodPercents($rows, $treatMissingPlanAsUnmet),
        );
    }

    /**
     * % = среднее процентов primary-параметров (колонка «Итог»).
     *
     * @param  Collection<int, TableReportRowData>  $rows
     * @return list<array{value: mixed, format: mixed, highlight?: bool}>
     */
    public function aggregateSummary(Collection $rows, bool $treatMissingPlanAsUnmet = true): array
    {
        return $this->buildPrimaryPercentSlots(
            $rows,
            static function (TableReportRowData $row, int $primaryIndex): array {
                $summary = $row->data->get('summary');
                $plan = $row->data->get('plan');

                $fact = is_array($summary) ? ($summary[$primaryIndex]['value'] ?? null) : null;
                $planValue = is_array($plan) ? ($plan[$primaryIndex]['value'] ?? null) : null;

                return [$fact, $planValue];
            },
            $treatMissingPlanAsUnmet,
        );
    }

    /**
     * % выполнения по каждому срезу детализации (day_* / week_* / month_*).
     *
     * @param  Collection<int, TableReportRowData>  $rows
     * @return array<string, list<array{value: mixed, format: mixed, highlight?: bool}>>
     */
    public function aggregatePeriodPercents(Collection $rows, bool $treatMissingPlanAsUnmet = true): array
    {
        $bucketKeys = [];
        foreach ($rows as $row) {
            foreach ($row->data->keys() as $key) {
                if (is_string($key) && preg_match('/^(day|week|month)_\d+$/', $key) === 1) {
                    $bucketKeys[$key] = true;
                }
            }
        }

        $result = [];
        foreach (array_keys($bucketKeys) as $bucketKey) {
            $result[$bucketKey] = $this->buildPrimaryPercentSlots(
                $rows,
                static function (TableReportRowData $row, int $primaryIndex) use ($bucketKey): array {
                    $slots = $row->data->get($bucketKey);
                    if (! is_array($slots) || ! isset($slots[$primaryIndex]) || ! is_array($slots[$primaryIndex])) {
                        return [null, null];
                    }

                    $slot = $slots[$primaryIndex];
                    $fact = is_array($slot['fact'] ?? null) ? ($slot['fact']['value'] ?? null) : null;
                    $plan = is_array($slot['plan'] ?? null) ? ($slot['plan']['value'] ?? null) : null;

                    return [$fact, $plan];
                },
                $treatMissingPlanAsUnmet,
            );
        }

        return $result;
    }

    /**
     * @param  Collection<int, TableReportRowData>  $rows
     * @return array{kind: string, value?: float}
     */
    public function aggregateBonuses(Collection $rows): array
    {
        $sum = 0.0;
        $hasAmount = false;

        foreach ($rows as $row) {
            $bonuses = $row->data->get('bonuses');
            if (! is_array($bonuses)) {
                continue;
            }
            if (($bonuses['kind'] ?? null) !== 'amount') {
                continue;
            }
            if (! is_numeric($bonuses['value'] ?? null)) {
                continue;
            }
            $sum += (float) $bonuses['value'];
            $hasAmount = true;
        }

        if (! $hasAmount) {
            return ['kind' => 'dash'];
        }

        return ['kind' => 'amount', 'value' => round($sum, 2)];
    }

    /**
     * @param  Collection<int, TableReportRowData>  $rows
     * @param  callable(TableReportRowData, int): array{0: mixed, 1: mixed}  $resolveFactAndPlan
     * @return list<array{value: mixed, format: mixed, highlight?: bool}>
     */
    private function buildPrimaryPercentSlots(
        Collection $rows,
        callable $resolveFactAndPlan,
        bool $treatMissingPlanAsUnmet,
    ): array {
        $percents = [];

        foreach ($rows as $row) {
            $primaryIndex = $this->resolvePrimaryIndex($row);
            if ($primaryIndex === null) {
                continue;
            }

            [$fact, $plan] = $resolveFactAndPlan($row, $primaryIndex);

            if (is_numeric($plan) && (float) $plan != 0.0) {
                $factValue = is_numeric($fact) ? (float) $fact : 0.0;
                $percents[] = ($factValue / (float) $plan) * 100;

                continue;
            }

            if ($treatMissingPlanAsUnmet) {
                $percents[] = 0.0;
            }
        }

        if ($percents === []) {
            return [];
        }

        return [[
            'value' => (int) round(array_sum($percents) / count($percents)),
            'format' => 'percent',
            'highlight' => true,
        ]];
    }

    private function resolvePrimaryIndex(TableReportRowData $row): ?int
    {
        $parameters = $row->data->get('parameter');
        if (! is_array($parameters)) {
            return null;
        }

        foreach ($parameters as $index => $meta) {
            if (! empty($meta['highlight'])) {
                return (int) $index;
            }
        }

        return null;
    }
}
