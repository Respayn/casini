<?php

namespace App\Data\Statistics;

use App\Data\TableReportColumnData;
use App\Enums\ChannelReportGrouping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class StatisticsReportQueryData extends Data implements Wireable
{
    use WireableData;

    /**
     * Выбранная группировка
     * @var ChannelReportGrouping
     */
    public ChannelReportGrouping $grouping = ChannelReportGrouping::NONE;

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
     * @param array|\Illuminate\Support\Collection $rates
     * @return StatisticsReportQueryData
     */
    public static function create(array|Collection $rates = []): StatisticsReportQueryData
    {
        if (is_array($rates)) {
            $rates = new Collection($rates);
        }

        $instance = new self();

        $instance->dateTo = Carbon::now();

        $colOrder = 0;

        $instance->columns = new Collection([
            new TableReportColumnData('manager', 'Менеджер', true, $colOrder++),
            new TableReportColumnData('client', 'Клиент', true, $colOrder++),
            new TableReportColumnData('client-project', 'Клиенто-проект', true, $colOrder++),
            new TableReportColumnData('client-project-id', 'ID', true, $colOrder++),
            new TableReportColumnData('service', 'Сервис', true, $colOrder++),
            new TableReportColumnData('department', 'Отдел', true, $colOrder++),
            new TableReportColumnData('kpi', 'KPI', true, $colOrder++),
            new TableReportColumnData('parameter', 'Параметр', true, $colOrder++),
            new TableReportColumnData('plan', 'План', true, $colOrder++),
            new TableReportColumnData('login', 'Логин', true, $colOrder++),
        ]);
        
        // здесь добавлять столбцы для временных интервалов
        // Добавляем столбцы для ставок с включенным параметром "Собирать статистику по отработанному времени?"
        // if ($rates->isNotEmpty()) {
        //     foreach ($rates as $rate) {
        //         $field = 'position_' . $rate->id;
        //         $instance->columns->add(new TableReportColumnData($field, $rate->name, true, $colOrder++, 'position', tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'));
        //     }
        // }

        // сумма по должностям и программингу, копирайтеру и ссылкам
        $instance->columns->add(new TableReportColumnData('summary', 'Итог', true, $colOrder++));
        $instance->columns->add(new TableReportColumnData('prediction', 'Прогноз', true, $colOrder++));
        $instance->columns->add(new TableReportColumnData('bonuses', 'Бонусы и гарантии', true, $colOrder++));
            
        return $instance;
    }
}
