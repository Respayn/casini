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

    public Carbon $dateFrom;

    public Carbon $dateTo;

    public bool $showInactive = false;

    public bool $includeVat = false;

    public function __construct() {}

    public static function create(): ChannelReportQueryData
    {
        $instance = new self();

        $instance->dateFrom = Carbon::now()->subMonth();
        $instance->dateTo = Carbon::now();

        $instance->columns = new Collection([
            new TableReportColumnData('department', 'Отдел', true, 0),
            new TableReportColumnData('tool', 'Инструмент', true, 1),
            new TableReportColumnData('client', 'Клиент', true, 2),
            new TableReportColumnData('client_project_id', 'ID клиенто-проекта', true, 3),
            new TableReportColumnData('login', 'Логин', true, 4),
            new TableReportColumnData('status', 'Статус', true, 5),
            new TableReportColumnData('manager', 'Менеджер', true, 6),
            new TableReportColumnData('specialist', 'Специалист', true, 7),
            new TableReportColumnData('kpi', 'KPI', true, 8),
            new TableReportColumnData('plan', 'План', true, 9),
            new TableReportColumnData('client_receipt', 'Чек клиента', true, 10),
            new TableReportColumnData('max_bonuses', 'Макс. бонусы', true, 11),
            new TableReportColumnData('programming', 'Программинг (час/₽)', true, 12),
            new TableReportColumnData('copyrighting', 'Копирайтер (час/₽)', true, 13),
            new TableReportColumnData('seo_links', 'SEO-ссылки (₽)', true, 14),
            // должности
            new TableReportColumnData('position_1', 'Помощник SEO-специалиста (час/₽)', true, 15),
            new TableReportColumnData('position_2', 'SEO-специалист (час/₽)', true, 16),
            new TableReportColumnData('position_3', 'Аналитик (час/₽)', true, 17),
            new TableReportColumnData('position_4', 'Менеджер ОРК (час/₽)', true, 18),
            // конец должностей
            new TableReportColumnData('summary_spendings', 'Расходы итого (₽)', true, 19),
            new TableReportColumnData('direct_budget', 'Остаток бюджета в Директе (₽)', true, 20),
            new TableReportColumnData('direct_spendings', 'Расход в Директе (₽)', true, 21),
        ]);
        return $instance;
    }
}
