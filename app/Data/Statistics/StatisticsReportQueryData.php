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
     * @var ChannelReportGrouping
     */
    public ChannelReportGrouping $grouping = ChannelReportGrouping::NONE;

    public StatisticsReportDetailLevel $detailLevel = StatisticsReportDetailLevel::BY_DAY;

    /**
     * Summary of columns
     * @var Collection<int, TableReportColumnData>
     */
    public Collection $columns;

    public Carbon $dateTo;

    public bool $showInactive = false;

    public bool $includeVat = false;

    public function __construct() {}

    /**
     * Summary of create
     * @return StatisticsReportQueryData
     */
    public static function create(
        StatisticsReportDetailLevel $detailLevel = StatisticsReportDetailLevel::BY_WEEK,
        Carbon $dateTo = new Carbon()
    ): StatisticsReportQueryData {
        $instance = new self();

        $instance->dateTo = $dateTo;
        $instance->detailLevel = $detailLevel;

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
            $daysCount = $dateTo->daysInMonth();
            $monthNum = $dateTo->month;
            for ($i = 1; $i <= $daysCount; $i++) {
                $label = Str::padLeft($i, 2, '0') . '.' . Str::padLeft($monthNum, 2, '0') . ' план/факт';
                $instance->columns->add(new TableReportColumnData("day_{$i}", $label, $colOrder++, component: 'fact', isSortable: false));
            }
        }

        if ($detailLevel === StatisticsReportDetailLevel::BY_WEEK) {
            $weekIntervals = DateTimeHelper::getMonthWeekIntervals($dateTo);
            foreach ($weekIntervals as $i => $weekInterval) {
                $label = $weekInterval['start']->format('d.m') . ' - ' . $weekInterval['end']->format('d.m')  . ' план/факт';
                $instance->columns->add(new TableReportColumnData("week_{$i}", $label, $colOrder++, component: 'fact', isSortable: false));
            }
        }

        if ($detailLevel === StatisticsReportDetailLevel::BY_MONTH) {
            $label = DateTimeHelper::getMonthName($dateTo->month);
            $instance->columns->add(new TableReportColumnData('month', $label, $colOrder++, component: 'fact', isSortable: false));
        }

        $instance->columns->add(new TableReportColumnData('summary', 'Итог', $colOrder++));
        $instance->columns->add(new TableReportColumnData('prediction', 'Прогноз', $colOrder++));
        $instance->columns->add(new TableReportColumnData('bonuses', 'Бонусы и гарантии', $colOrder++));

        return $instance;
    }
}
