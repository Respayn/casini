<?php

namespace App\Domain\Statistics\Services;

use App\Models\Project;
use App\Models\ProjectBonusCondition;
use App\Services\BonusService;
use Illuminate\Support\Carbon;
use Src\Domain\ValueObjects\Kpi;
use Src\Planning\Application\ProjectPlanService;

/**
 * Колонки «Итог», «Прогноз», «Бонусы и гарантии» для Статистики.
 */
class StatisticsClosingColumnsCalculator
{
    public function __construct(
        private ProjectPlanService $projectPlanService,
        private BonusService $bonusService,
    ) {}

    /**
     * @param  list<array{value: mixed, format: mixed}>  $planCell
     * @param  array<string, float>  $spendByDay
     * @param  array<string, int>  $leadsByDay
     * @param  array<string, float>  $topPercentsByDay
     * @param  array<string, float|int>  $visitsByDay  пока пусто (Метрика)
     * @return array{
     *     summary: list<array{value: mixed, format: mixed, plan_percent?: int|null}>,
     *     prediction: list<array{value: mixed, format: mixed, kind?: string}>,
     *     bonuses: array{kind: string, value?: float}
     * }
     */
    public function calculate(
        Project $project,
        Carbon $month,
        array $planCell,
        array $spendByDay,
        array $leadsByDay = [],
        array $topPercentsByDay = [],
        array $visitsByDay = [],
        ?Carbon $today = null,
    ): array {
        $today ??= Carbon::now()->startOfDay();
        $parameterCodes = $this->projectPlanService->getParameterCodes($project->project_type, $project->kpi);
        $parametersMeta = $this->projectPlanService->getKpiParametersSchemaForStatistics(
            $project->project_type,
            $project->kpi,
        );

        $summary = $this->buildSummary(
            $parameterCodes,
            $parametersMeta,
            $planCell,
            $month,
            $spendByDay,
            $leadsByDay,
            $topPercentsByDay,
            $visitsByDay,
        );

        $prediction = $this->buildPrediction(
            $project->kpi,
            $parameterCodes,
            $parametersMeta,
            $month,
            $spendByDay,
            $leadsByDay,
            $visitsByDay,
            $today,
        );

        $bonuses = $this->buildBonuses(
            $project,
            $parameterCodes,
            $parametersMeta,
            $planCell,
            $summary,
        );

        return [
            'summary' => $summary,
            'prediction' => $prediction,
            'bonuses' => $bonuses,
        ];
    }

    /**
     * @param  list<string>  $parameterCodes
     * @param  list<array{name: string, highlight: bool}>  $parametersMeta
     * @param  list<array{value: mixed, format: mixed}>  $planCell
     * @param  array<string, float>  $spendByDay
     * @param  array<string, int>  $leadsByDay
     * @param  array<string, float>  $topPercentsByDay
     * @param  array<string, float|int>  $visitsByDay
     * @return list<array{value: mixed, format: mixed, plan_percent?: int|null, highlight?: bool}>
     */
    private function buildSummary(
        array $parameterCodes,
        array $parametersMeta,
        array $planCell,
        Carbon $month,
        array $spendByDay,
        array $leadsByDay,
        array $topPercentsByDay,
        array $visitsByDay,
    ): array {
        $lastDayKey = $month->copy()->endOfMonth()->toDateString();
        $monthStart = $month->copy()->startOfMonth()->startOfDay();
        $monthEnd = $month->copy()->endOfMonth()->startOfDay();

        $budgetSum = $this->sumDays($spendByDay, $monthStart, $monthEnd);
        $leadsSum = $this->sumDaysInt($leadsByDay, $monthStart, $monthEnd);
        $visitsSum = $this->sumDays($visitsByDay, $monthStart, $monthEnd);

        $slots = [];
        foreach ($parameterCodes as $index => $code) {
            $isPrimary = ! empty($parametersMeta[$index]['highlight']);
            $hasLastDay = $this->hasLastDaySlice(
                $code,
                $lastDayKey,
                $spendByDay,
                $leadsByDay,
                $topPercentsByDay,
                $visitsByDay,
            );

            if (! $hasLastDay) {
                $slots[] = [
                    'value' => null,
                    'format' => null,
                    'plan_percent' => null,
                    'highlight' => $isPrimary,
                ];

                continue;
            }

            $slot = match ($code) {
                'budget' => [
                    'value' => $budgetSum,
                    'format' => 'currency',
                ],
                'leads' => [
                    'value' => $leadsSum,
                    'format' => null,
                ],
                'visits' => [
                    'value' => $visitsSum,
                    'format' => null,
                ],
                'conversions' => [
                    'value' => null,
                    'format' => null,
                ],
                'cpl' => [
                    'value' => $this->divide($budgetSum, $leadsSum !== null ? (float) $leadsSum : null),
                    'format' => 'currency',
                ],
                'cpc' => [
                    'value' => $this->divide($budgetSum, $visitsSum),
                    'format' => 'currency',
                ],
                'top_percent' => [
                    // % не суммируется: в итог — значение среза за последний день месяца.
                    'value' => isset($topPercentsByDay[$lastDayKey])
                        ? round((float) $topPercentsByDay[$lastDayKey], 1)
                        : null,
                    'format' => 'percent',
                ],
                default => ['value' => null, 'format' => null],
            };

            $slot['plan_percent'] = $this->planPercent(
                $slot['value'] ?? null,
                $planCell[$index]['value'] ?? null,
            );
            $slot['highlight'] = $isPrimary;
            $slots[] = $slot;
        }

        return $slots;
    }

    /**
     * % выполнения плана: итог / план × 100. Без плана или при нулевом плане — null.
     */
    private function planPercent(mixed $fact, mixed $plan): ?int
    {
        if (! is_numeric($fact) || ! is_numeric($plan)) {
            return null;
        }

        $plan = (float) $plan;
        if ($plan == 0.0) {
            return null;
        }

        return (int) round(((float) $fact / $plan) * 100);
    }

    /**
     * @param  list<string>  $parameterCodes
     * @param  list<array{name: string, highlight: bool}>  $parametersMeta
     * @param  array<string, float>  $spendByDay
     * @param  array<string, int>  $leadsByDay
     * @param  array<string, float|int>  $visitsByDay
     * @return list<array{value: mixed, format: mixed, kind?: string}>
     */
    private function buildPrediction(
        Kpi $kpi,
        array $parameterCodes,
        array $parametersMeta,
        Carbon $month,
        array $spendByDay,
        array $leadsByDay,
        array $visitsByDay,
        Carbon $today,
    ): array {
        $slotCount = count($parameterCodes);
        $empty = array_fill(0, $slotCount, ['value' => null, 'format' => null]);

        if (! in_array($kpi, [Kpi::TRAFFIC, Kpi::LEADS], true)) {
            return $empty;
        }

        $primaryIndex = $this->primaryParameterIndex($parametersMeta);
        if ($primaryIndex === null) {
            return $empty;
        }

        $primaryCode = $parameterCodes[$primaryIndex] ?? null;
        if (! in_array($primaryCode, ['visits', 'leads'], true)) {
            return $empty;
        }

        $monthStart = $month->copy()->startOfMonth()->startOfDay();
        $monthEnd = $month->copy()->endOfMonth()->startOfDay();
        $daysInMonth = $month->daysInMonth;

        $elapsedEnd = $this->elapsedPeriodEnd($month, $today);
        if ($elapsedEnd === null) {
            return $empty;
        }

        $sourceByDay = $primaryCode === 'leads' ? $leadsByDay : $visitsByDay;
        $daysWithData = $this->countDaysWithData($sourceByDay, $monthStart, $elapsedEnd);

        $slots = $empty;
        if ($daysWithData < 3) {
            $slots[$primaryIndex] = [
                'value' => null,
                'format' => null,
                'kind' => 'insufficient',
            ];

            return $slots;
        }

        $fact = $primaryCode === 'leads'
            ? $this->sumDaysInt($sourceByDay, $monthStart, $elapsedEnd)
            : $this->sumDays($sourceByDay, $monthStart, $elapsedEnd);

        $elapsedDays = (int) $monthStart->diffInDays($elapsedEnd) + 1;
        if ($fact === null || $elapsedDays <= 0) {
            return $empty;
        }

        $predicted = (float) $fact / $elapsedDays * $daysInMonth;
        $slots[$primaryIndex] = [
            'value' => $primaryCode === 'leads'
                ? (int) round($predicted)
                : round($predicted, abs($predicted - round($predicted)) < 0.001 ? 0 : 2),
            'format' => null,
            'kind' => 'forecast',
        ];

        return $slots;
    }

    /**
     * @param  list<string>  $parameterCodes
     * @param  list<array{name: string, highlight: bool}>  $parametersMeta
     * @param  list<array{value: mixed, format: mixed}>  $planCell
     * @param  list<array{value: mixed, format: mixed}>  $summary
     * @return array{kind: string, value?: float}
     */
    private function buildBonuses(
        Project $project,
        array $parameterCodes,
        array $parametersMeta,
        array $planCell,
        array $summary,
    ): array {
        $primaryIndex = $this->primaryParameterIndex($parametersMeta);
        if ($primaryIndex === null) {
            return ['kind' => 'dash'];
        }

        $summaryValue = $summary[$primaryIndex]['value'] ?? null;
        if (! is_numeric($summaryValue)) {
            return ['kind' => 'dash'];
        }

        /** @var ProjectBonusCondition|null $condition */
        $condition = $project->bonusCondition;
        if ($condition === null || ! $condition->bonuses_enabled) {
            return ['kind' => 'not_configured'];
        }

        if ($condition->calculate_in_percentage && ! is_numeric($condition->client_payment)) {
            return ['kind' => 'fill_check'];
        }

        $planValue = $planCell[$primaryIndex]['value'] ?? null;
        if (! is_numeric($planValue) || (float) $planValue == 0.0) {
            return ['kind' => 'amount', 'value' => 0.0];
        }

        $performance = ((float) $summaryValue / (float) $planValue) * 100;

        if ($condition->relationLoaded('intervals') === false) {
            $condition->load('intervals');
        }

        $amount = $this->bonusService->calculateBonuses($condition, $performance);

        return ['kind' => 'amount', 'value' => (float) $amount];
    }

    /**
     * @param  array<string, float|int>  $spendByDay
     * @param  array<string, int>  $leadsByDay
     * @param  array<string, float>  $topPercentsByDay
     * @param  array<string, float|int>  $visitsByDay
     */
    private function hasLastDaySlice(
        string $code,
        string $lastDayKey,
        array $spendByDay,
        array $leadsByDay,
        array $topPercentsByDay,
        array $visitsByDay,
    ): bool {
        return match ($code) {
            'budget' => array_key_exists($lastDayKey, $spendByDay),
            'leads' => array_key_exists($lastDayKey, $leadsByDay),
            'visits' => array_key_exists($lastDayKey, $visitsByDay),
            'conversions' => false,
            'top_percent' => array_key_exists($lastDayKey, $topPercentsByDay),
            'cpl' => array_key_exists($lastDayKey, $spendByDay)
                && array_key_exists($lastDayKey, $leadsByDay),
            'cpc' => array_key_exists($lastDayKey, $spendByDay)
                && array_key_exists($lastDayKey, $visitsByDay),
            default => false,
        };
    }

    /**
     * Конец периода для прогноза: вчера для текущего месяца (ночной съём),
     * последний день месяца — для закрытого.
     */
    private function elapsedPeriodEnd(Carbon $month, Carbon $today): ?Carbon
    {
        $monthStart = $month->copy()->startOfMonth()->startOfDay();
        $monthEnd = $month->copy()->endOfMonth()->startOfDay();

        if ($today->lt($monthStart)) {
            return null;
        }

        if ($today->gt($monthEnd)) {
            return $monthEnd;
        }

        // Текущий месяц: съём за вчера → прошедшие дни до вчера включительно.
        $yesterday = $today->copy()->subDay()->startOfDay();
        if ($yesterday->lt($monthStart)) {
            return null;
        }

        return $yesterday->lte($monthEnd) ? $yesterday : $monthEnd;
    }

    /**
     * @param  list<array{name: string, highlight: bool}>  $parametersMeta
     */
    private function primaryParameterIndex(array $parametersMeta): ?int
    {
        foreach ($parametersMeta as $index => $meta) {
            if (! empty($meta['highlight'])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<string, float|int>  $byDay
     */
    private function sumDays(array $byDay, Carbon $from, Carbon $to): ?float
    {
        if ($byDay === []) {
            return null;
        }

        $total = 0.0;
        $hasData = false;

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            if (! array_key_exists($key, $byDay)) {
                continue;
            }
            $hasData = true;
            $total += (float) $byDay[$key];
        }

        return $hasData ? round($total, 2) : null;
    }

    /**
     * @param  array<string, int>  $byDay
     */
    private function sumDaysInt(array $byDay, Carbon $from, Carbon $to): ?int
    {
        if ($byDay === []) {
            return null;
        }

        $total = 0;
        $hasData = false;

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            if (! array_key_exists($key, $byDay)) {
                continue;
            }
            $hasData = true;
            $total += (int) $byDay[$key];
        }

        return $hasData ? $total : null;
    }

    /**
     * @param  array<string, float|int>  $byDay
     */
    private function countDaysWithData(array $byDay, Carbon $from, Carbon $to): int
    {
        $count = 0;
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            if (array_key_exists($day->toDateString(), $byDay)) {
                $count++;
            }
        }

        return $count;
    }

    private function divide(int|float|null $numerator, int|float|null $denominator): ?float
    {
        if ($numerator === null || $denominator === null) {
            return null;
        }

        $denominator = (float) $denominator;
        if ($denominator <= 0.0) {
            return null;
        }

        return round((float) $numerator / $denominator, 2);
    }
}
