<?php

namespace App\Domain\Statistics\Services;

use App\Data\TableReportRowData;
use Illuminate\Support\Collection;

/**
 * Итого по колонкам «Итог» и «Бонусы и гарантии» в рамках группировки / всей таблицы.
 *
 * В колонке «Итог» в строках Итого — только % выполнения по основным (primary) параметрам.
 */
class StatisticsClosingColumnsAggregator
{
    /**
     * @param  Collection<int, TableReportRowData>  $rows
     * @return array{summary: list<array{value: mixed, format: mixed}>, bonuses: array{kind: string, value?: float}}
     */
    public function aggregate(Collection $rows): array
    {
        return [
            'summary' => $this->aggregateSummary($rows),
            'bonuses' => $this->aggregateBonuses($rows),
        ];
    }

    /**
     * % = сумма primary-фактов / сумма primary-планов × 100.
     *
     * @param  Collection<int, TableReportRowData>  $rows
     * @return list<array{value: mixed, format: mixed}>
     */
    public function aggregateSummary(Collection $rows): array
    {
        $factSum = 0.0;
        $planSum = 0.0;
        $hasFact = false;
        $hasPlan = false;

        foreach ($rows as $row) {
            $parameters = $row->data->get('parameter');
            $summary = $row->data->get('summary');
            $plan = $row->data->get('plan');
            if (! is_array($parameters) || ! is_array($summary) || $summary === []) {
                continue;
            }

            $primaryIndex = null;
            foreach ($parameters as $index => $meta) {
                if (! empty($meta['highlight'])) {
                    $primaryIndex = $index;
                    break;
                }
            }
            if ($primaryIndex === null || ! isset($summary[$primaryIndex])) {
                continue;
            }

            if (is_numeric($summary[$primaryIndex]['value'] ?? null)) {
                $factSum += (float) $summary[$primaryIndex]['value'];
                $hasFact = true;
            }
            if (is_array($plan) && is_numeric($plan[$primaryIndex]['value'] ?? null)) {
                $planSum += (float) $plan[$primaryIndex]['value'];
                $hasPlan = true;
            }
        }

        if (! $hasFact || ! $hasPlan || $planSum == 0.0) {
            return [];
        }

        return [[
            'value' => (int) round(($factSum / $planSum) * 100),
            'format' => 'percent',
            'highlight' => true,
        ]];
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
}
