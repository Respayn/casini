<?php

namespace App\Data\Channels;

use App\Data\TableReportColumnData;
use App\Enums\ChannelReportGrouping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ChannelReportQueryData extends Data implements Wireable
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
     * @return ChannelReportQueryData
     */
    public static function create(array|Collection $rates = []): ChannelReportQueryData
    {
        if (is_array($rates)) {
            $rates = new Collection($rates);
        }

        $instance = new self();

        $instance->dateTo = Carbon::now();

        $colOrder = 0;

        $instance->columns = new Collection([
            new TableReportColumnData('department', 'Отдел', true, $colOrder++),
            new TableReportColumnData('tool', 'Инструмент', true, $colOrder++),
            new TableReportColumnData('client', 'Клиент', true, $colOrder++),
            new TableReportColumnData('client-project', 'Клиенто-проект', true, $colOrder++),
            new TableReportColumnData('client-project-id', 'ID', true, $colOrder++),
            new TableReportColumnData('login', 'Логин', true, $colOrder++),
            new TableReportColumnData('status', 'Статус', true, $colOrder++),
            new TableReportColumnData('manager', 'Менеджер', true, $colOrder++),
            new TableReportColumnData('specialist', 'Специалист', true, $colOrder++),
            new TableReportColumnData('kpi', 'KPI', true, $colOrder++),
            new TableReportColumnData('plan', 'План', true, $colOrder++),
            new TableReportColumnData('client-receipt', 'Чек клиента', true, $colOrder++),
            new TableReportColumnData('max-bonuses', 'Макс. бонусы', true, $colOrder++, tooltip: 'Максимальное количество бонусов доступное в канале, задается в настройках канала'),
            new TableReportColumnData('acts', 'Акты', true, $colOrder++),
            new TableReportColumnData('programming', 'Программинг (час/₽)', true, $colOrder++, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('copyrighting', 'Копирайтер (знак/₽)', true, $colOrder++, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('seo-links', 'SEO-ссылки (₽)', true, $colOrder++, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
        ]);

        // Добавляем столбцы для ставок с включенным параметром "Собирать статистику по отработанному времени?"
        if ($rates->isNotEmpty()) {
            foreach ($rates as $rate) {
                $field = 'position_' . $rate->id;
                $instance->columns->add(new TableReportColumnData($field, $rate->name, true, $colOrder++, 'position', tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'));
            }
        }

        // сумма по должностям и программингу, копирайтеру и ссылкам
        $instance->columns->add(new TableReportColumnData('summary-spendings', 'Расходы итого (₽)', true, $colOrder++));
        $instance->columns->add(new TableReportColumnData('direct-budget', 'Остаток бюджета в Директе (₽)', true, $colOrder++, tooltip: 'Остаток бюджета нельзя посмотреть за предыдущий период, только на текущее время, если нужно обновить баланс сейчас - кликните на ячейку и данные обновятся'));
        $instance->columns->add(new TableReportColumnData('direct-spendings', 'Расход в Директе (₽)', true, $colOrder++));
            
        return $instance;
    }
}
