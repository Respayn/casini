<?php

namespace App\Data\Statistics;

use App\Data\TableReportColumnData;
use App\Domain\Statistics\Enums\StatisticsReportDetailLevel;
use App\Enums\ChannelReportGrouping;
use App\Helpers\DateTimeHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;
use Str;

class StatisticsReportQueryData extends Data implements Wireable
{
    use WireableData;

    /**
     * Выбранная группировка
     *
     * @var ChannelReportGrouping
     */
    public ChannelReportGrouping $grouping = ChannelReportGrouping::NONE;

    public StatisticsReportDetailLevel $detailLevel = StatisticsReportDetailLevel::BY_DAY;

    /**
     * @var Collection<int, TableReportColumnData>
     */
    public Collection $columns;

    public Carbon $dateFrom;

    public Carbon $dateTo;

    public bool $showInactive = false;

    public bool $includeVat = false;

    /**
     * План и факт накапливаются в отчёте (UI настроек отчёта).
     */
    public string $accumulateData = 'Y';

    public function __construct() {}

    public static function create(
        StatisticsReportDetailLevel $detailLevel = StatisticsReportDetailLevel::BY_WEEK,
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null,
    ): StatisticsReportQueryData {
        $instance = new self();

        $currentMonth = Carbon::now()->startOfMonth()->startOfDay();
        $instance->dateFrom = ($dateFrom ?? $currentMonth)->copy()->startOfMonth()->startOfDay();
        $instance->dateTo = ($dateTo ?? $currentMonth)->copy()->startOfMonth()->startOfDay();
        $instance->detailLevel = $detailLevel;
        $instance->clampPeriodToPresent();

        $gridMonth = $instance->detailGridMonth();

        $colOrder = 0;

        $instance->columns = new Collection([
            new TableReportColumnData('manager', 'Менеджер', $colOrder++),
            new TableReportColumnData('client', 'Клиент', $colOrder++),
            new TableReportColumnData('client-project', 'Клиенто-проект', $colOrder++),
            new TableReportColumnData('client-project-id', 'ID', $colOrder++),
            new TableReportColumnData('service', 'Сервис', $colOrder++),
            new TableReportColumnData('department', 'Отдел', $colOrder++),
            new TableReportColumnData('kpi', 'KPI', $colOrder++),
            new TableReportColumnData('parameter', 'Параметр', $colOrder++),
            new TableReportColumnData('plan', 'План', $colOrder++),
            new TableReportColumnData('login', 'Логин', $colOrder++),
        ]);

        if ($detailLevel === StatisticsReportDetailLevel::BY_DAY) {
            $daysCount = $gridMonth->daysInMonth();
            $monthNum = $gridMonth->month;
            for ($i = 1; $i <= $daysCount; $i++) {
                $label = Str::padLeft($i, 2, '0').'.'.Str::padLeft($monthNum, 2, '0');
                $instance->columns->add(new TableReportColumnData("day_{$i}", $label, $colOrder++, component: 'fact', isSortable: false));
            }
        }

        if ($detailLevel === StatisticsReportDetailLevel::BY_WEEK) {
            $weekIntervals = DateTimeHelper::getMonthWeekIntervals($gridMonth);
            foreach ($weekIntervals as $i => $weekInterval) {
                $label = $weekInterval['start']->format('d.m').' - '.$weekInterval['end']->format('d.m');
                $instance->columns->add(new TableReportColumnData("week_{$i}", $label, $colOrder++, component: 'fact', isSortable: false));
            }
        }

        if ($detailLevel === StatisticsReportDetailLevel::BY_MONTH) {
            $label = DateTimeHelper::getMonthName($gridMonth->month);
            $instance->columns->add(new TableReportColumnData('month', $label, $colOrder++, component: 'fact', isSortable: false));
        }

        $instance->columns->add(new TableReportColumnData('summary', 'Итог', $colOrder++));
        $instance->columns->add(new TableReportColumnData('prediction', 'Прогноз', $colOrder++));
        $instance->columns->add(new TableReportColumnData('bonuses', 'Бонусы и гарантии', $colOrder++));

        return $instance;
    }

    /**
     * Месяц для колонок детализации: один месяц периода либо dateTo при интервале.
     */
    public function detailGridMonth(): Carbon
    {
        if ($this->isSingleMonthPeriod()) {
            return $this->dateFrom->copy()->startOfMonth()->startOfDay();
        }

        return $this->dateTo->copy()->startOfMonth()->startOfDay();
    }

    public function isSingleMonthPeriod(): bool
    {
        return $this->dateFrom->year === $this->dateTo->year
            && $this->dateFrom->month === $this->dateTo->month;
    }

    /**
     * Нормализация: начало месяца, не позже текущего месяца, dateFrom <= dateTo.
     */
    public function clampPeriodToPresent(): void
    {
        $currentMonth = Carbon::now()->startOfMonth()->startOfDay();

        $this->dateFrom = $this->dateFrom->copy()->startOfMonth()->startOfDay();
        $this->dateTo = $this->dateTo->copy()->startOfMonth()->startOfDay();

        if ($this->dateFrom->greaterThan($currentMonth)) {
            $this->dateFrom = $currentMonth->copy();
        }

        if ($this->dateTo->greaterThan($currentMonth)) {
            $this->dateTo = $currentMonth->copy();
        }

        if ($this->dateFrom->greaterThan($this->dateTo)) {
            $this->dateTo = $this->dateFrom->copy();
        }
    }
}
