<?php

namespace App\Data;

use App\Data\TableReportColumnData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class PlanningReportQueryData extends Data implements Wireable
{
    use WireableData;

    public int $year;

    /**
     * Summary of columns
     * @var Collection<int, TableReportColumnData>
     */
    public Collection $columns;

    public function __construct()
    {
        $this->year = Carbon::now()->year;
    }

    /**
     * Summary of create
     * @return PlanningReportQueryData
     */
    public static function create(): PlanningReportQueryData
    {
        $instance = new self();

        $colOrder = 0;

        $instance->columns = new Collection([
            new TableReportColumnData('client', 'Клиент', $colOrder++),
            new TableReportColumnData('client-project', 'Клиенто-проект', $colOrder++),
            new TableReportColumnData('client-project-created-at', 'Клиенто-проект создан', $colOrder++),
            new TableReportColumnData('client-project-id', 'ID', $colOrder++),
            new TableReportColumnData('department', 'Отдел', $colOrder++),
            new TableReportColumnData('kpi', 'KPI', $colOrder++),
            new TableReportColumnData('parameter', 'Параметр', $colOrder++),

            new TableReportColumnData('january', 'Январь', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('february', 'Февраль', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('march', 'Март', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('quarter_1', 'Согласование', $colOrder++, component: 'agreement'),
            
            new TableReportColumnData('april', 'Апрель', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('may', 'Май', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('june', 'Июнь', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('quarter_2', 'Согласование', $colOrder++, component: 'agreement'),
            
            new TableReportColumnData('july', 'Июль', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('august', 'Август', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('september', 'Сентябрь', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('quarter_3', 'Согласование', $colOrder++, component: 'agreement'),
            
            new TableReportColumnData('october', 'Октябрь', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('november', 'Ноябрь', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('december', 'Декабрь', $colOrder++, component: 'month-plan'),
            new TableReportColumnData('quarter_4', 'Согласование', $colOrder++, component: 'agreement'),
        ]);

        return $instance;
    }
}
