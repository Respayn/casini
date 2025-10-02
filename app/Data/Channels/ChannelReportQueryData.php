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

    public static function create(): ChannelReportQueryData
    {
        $instance = new self();

        $instance->dateTo = Carbon::now();

        $instance->columns = new Collection([
            new TableReportColumnData('department', 'Отдел', true, 0),
            new TableReportColumnData('tool', 'Инструмент', true, 1),
            new TableReportColumnData('client', 'Клиент', true, 2),
            new TableReportColumnData('client_project', 'Клиенто-проект', true, 3),
            new TableReportColumnData('client_project_id', 'ID', true, 4),
            new TableReportColumnData('login', 'Логин', true, 5),
            new TableReportColumnData('status', 'Статус', true, 6),
            new TableReportColumnData('manager', 'Менеджер', true, 7),
            new TableReportColumnData('specialist', 'Специалист', true, 8),
            new TableReportColumnData('kpi', 'KPI', true, 9),
            new TableReportColumnData('plan', 'План', true, 10),
            new TableReportColumnData('client_receipt', 'Чек клиента', true, 11),
            new TableReportColumnData('max_bonuses', 'Макс. бонусы', true, 12, tooltip: 'Максимальное количество бонусов доступное в канале, задается в настройках канала'),
            new TableReportColumnData('acts', 'Акты', true, 13),
            new TableReportColumnData('programming', 'Программинг (час/₽)', true, 14, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('copyrighting', 'Копирайтер (знак/₽)', true, 15, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('seo_links', 'SEO-ссылки (₽)', true, 16, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            // должности
            new TableReportColumnData('position_1', 'Помощник SEO-специалиста (час/₽)', true, 17, 'position', tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('position_2', 'SEO-специалист (час/₽)', true, 18, 'position', tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('position_3', 'Аналитик (час/₽)', true, 19, 'position', tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('position_4', 'Менеджер ОРК (час/₽)', true, 20, 'position', tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            // конец должностей
            // сумма по должностям и программингу, копирайтеру и ссылкам
            new TableReportColumnData('summary_spendings', 'Расходы итого (₽)', true, 21),
            new TableReportColumnData('direct_budget', 'Остаток бюджета в Директе (₽)', true, 22, tooltip: 'Остаток бюджета нельзя посмотреть за предыдущий период, только на текущее время, если нужно обновить баланс сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('direct_spendings', 'Расход в Директе (₽)', true, 23),
        ]);
        return $instance;
    }
}
