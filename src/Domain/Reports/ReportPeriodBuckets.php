<?php

namespace Src\Domain\Reports;

use DateTimeImmutable;
use Src\Domain\ValueObjects\DateTimeRange;

final class ReportPeriodBuckets
{
    /**
     * @param  list<ReportPeriodBucket>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $expandColumns,
        public readonly DateTimeRange $reportingMonth,
        public readonly string $reportingMonthKey,
    ) {}

    public static function make(
        DateTimeRange $period,
        ReportColumnGrouping $grouping = ReportColumnGrouping::Months,
        ReportDateSort $sort = ReportDateSort::NewestFirst,
    ): self {
        $start = $period->getStart();
        $end = $period->getEnd();

        if ($start === null || $end === null) {
            return new self([], false, $period, '');
        }

        $months = self::monthsBetween($start, $end);
        $reporting = self::monthRange($end);
        $reportingKey = $end->format('Y-m');
        $expand = count($months) > 1;

        $grouped = match ($grouping) {
            ReportColumnGrouping::Months => self::monthBuckets($months),
            ReportColumnGrouping::Quarters => self::quarterBuckets($months),
            ReportColumnGrouping::Years => self::yearBuckets($months),
        };

        if ($sort === ReportDateSort::NewestFirst) {
            $grouped = array_reverse($grouped);
        }

        return new self($grouped, $expand, $reporting, $reportingKey);
    }

    /**
     * @return list<string>
     */
    public function labels(): array
    {
        $labels = array_map(static fn (ReportPeriodBucket $bucket): string => $bucket->label, $this->items);
        $labels[] = 'Итого';

        return $labels;
    }

    /**
     * @param  array<string, int|float|string|null>  $valuesByMonth  ключ Y-m или Y-m-d
     * @return list<string>
     */
    public function seriesFromMonthly(
        array $valuesByMonth,
        string $empty = '0',
        bool $sumTotal = true,
    ): array {
        $normalized = [];
        foreach ($valuesByMonth as $month => $value) {
            $normalized[substr((string) $month, 0, 7)] = $value;
        }

        $series = [];
        foreach ($this->items as $bucket) {
            $series[] = $this->bucketValue($bucket, $normalized, $empty, $sumTotal);
        }

        $series[] = $this->totalValue($normalized, $empty, $sumTotal);

        return $series;
    }

    /**
     * @param  array<string, int|float|string|null>  $valuesByMonth
     */
    private function bucketValue(
        ReportPeriodBucket $bucket,
        array $valuesByMonth,
        string $empty,
        bool $sumTotal,
    ): string {
        $sum = 0.0;
        $hasNumber = false;
        $matched = [];

        foreach ($valuesByMonth as $month => $value) {
            if (! $bucket->containsMonthKey((string) $month)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $matched[substr((string) $month, 0, 7)] = $value;
            if (is_numeric($value)) {
                $sum += (float) $value;
                $hasNumber = true;
            }
        }

        if ($sumTotal) {
            return $hasNumber ? self::formatNumber($sum) : $empty;
        }

        if ($matched === []) {
            return $empty;
        }

        ksort($matched);

        return (string) end($matched);
    }

    /**
     * @param  array<string, int|float|string|null>  $valuesByMonth
     */
    private function totalValue(array $valuesByMonth, string $empty, bool $sumTotal): string
    {
        if ($sumTotal) {
            $sum = 0.0;
            $hasNumber = false;
            foreach ($valuesByMonth as $value) {
                if ($value === null || $value === '' || ! is_numeric($value)) {
                    continue;
                }
                $sum += (float) $value;
                $hasNumber = true;
            }

            return $hasNumber ? self::formatNumber($sum) : $empty;
        }

        $reporting = $valuesByMonth[$this->reportingMonthKey] ?? null;
        if ($reporting === null || $reporting === '') {
            return $empty;
        }

        return (string) $reporting;
    }

    private static function formatNumber(float $value): string
    {
        if (abs($value - (int) $value) < 0.0001) {
            return (string) (int) $value;
        }

        return (string) round($value, 1);
    }

    /**
     * @return list<DateTimeImmutable>
     */
    private static function monthsBetween(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $months = [];
        $current = $start->modify('first day of this month')->setTime(0, 0);
        $last = $end->modify('first day of this month')->setTime(0, 0);

        while ($current <= $last) {
            $months[] = $current;
            $current = $current->modify('+1 month');
        }

        return $months;
    }

    /**
     * @param  list<DateTimeImmutable>  $months
     * @return list<ReportPeriodBucket>
     */
    private static function monthBuckets(array $months): array
    {
        $buckets = [];
        foreach ($months as $month) {
            $buckets[] = new ReportPeriodBucket(
                $month->format('Y-m'),
                self::monthLabel($month),
                $month,
                $month->modify('last day of this month')->setTime(23, 59, 59),
            );
        }

        return $buckets;
    }

    /**
     * @param  list<DateTimeImmutable>  $months
     * @return list<ReportPeriodBucket>
     */
    private static function quarterBuckets(array $months): array
    {
        $groups = [];
        foreach ($months as $month) {
            $quarter = (int) ceil(((int) $month->format('n')) / 3);
            $key = $month->format('Y').'-q'.$quarter;
            $groups[$key][] = $month;
        }

        $buckets = [];
        foreach ($groups as $key => $groupMonths) {
            $first = $groupMonths[0];
            $last = $groupMonths[array_key_last($groupMonths)];
            $quarter = (int) ceil(((int) $first->format('n')) / 3);
            $buckets[] = new ReportPeriodBucket(
                $key,
                $quarter.' кв. '.$first->format('Y'),
                $first,
                $last->modify('last day of this month')->setTime(23, 59, 59),
            );
        }

        return $buckets;
    }

    /**
     * @param  list<DateTimeImmutable>  $months
     * @return list<ReportPeriodBucket>
     */
    private static function yearBuckets(array $months): array
    {
        $groups = [];
        foreach ($months as $month) {
            $groups[$month->format('Y')][] = $month;
        }

        $buckets = [];
        foreach ($groups as $year => $groupMonths) {
            $first = $groupMonths[0];
            $last = $groupMonths[array_key_last($groupMonths)];
            $buckets[] = new ReportPeriodBucket(
                (string) $year,
                (string) $year,
                $first,
                $last->modify('last day of this month')->setTime(23, 59, 59),
            );
        }

        return $buckets;
    }

    private static function monthRange(DateTimeImmutable $month): DateTimeRange
    {
        $start = $month->modify('first day of this month')->setTime(0, 0);

        return new DateTimeRange(
            $start,
            $month->modify('last day of this month')->setTime(23, 59, 59),
        );
    }

    private static function monthLabel(DateTimeImmutable $month): string
    {
        $months = [
            1 => 'Янв',
            2 => 'Фев',
            3 => 'Мар',
            4 => 'Апр',
            5 => 'Май',
            6 => 'Июн',
            7 => 'Июл',
            8 => 'Авг',
            9 => 'Сен',
            10 => 'Окт',
            11 => 'Ноя',
            12 => 'Дек',
        ];

        return $months[(int) $month->format('n')].' '.$month->format('Y');
    }
}
