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

        $currentMonth = Carbon::now()->startOfMonth()->startOfDay();
        $instance->dateFrom = $currentMonth->copy();
        $instance->dateTo = $currentMonth->copy();

        $colOrder = 0;

        $instance->columns = new Collection([
            new TableReportColumnData('project-type', 'Тип клиенто-проекта', $colOrder++),
            new TableReportColumnData('tool', 'Интеграции', $colOrder++),
            new TableReportColumnData('client', 'Клиент', $colOrder++),
            new TableReportColumnData('client-project', 'Клиенто-проект', $colOrder++),
            new TableReportColumnData('client-project-id', 'ID', $colOrder++),
            new TableReportColumnData('login', 'Логин', $colOrder++),
            new TableReportColumnData('status', 'Статус', $colOrder++),
            new TableReportColumnData('manager', 'Менеджер', $colOrder++),
            new TableReportColumnData('specialist', 'Специалист', $colOrder++),
            new TableReportColumnData('kpi', 'KPI', $colOrder++),
            new TableReportColumnData('plan', 'План', $colOrder++),
            new TableReportColumnData('client-receipt', 'Чек клиента', $colOrder++),
            new TableReportColumnData('max-bonuses', 'Макс. бонусы', $colOrder++, tooltip: 'Максимальное количество бонусов доступное в канале, задается в настройках канала'),
            new TableReportColumnData('acts', 'Акты', $colOrder++),
            new TableReportColumnData('programming', 'Программинг (час/₽)', $colOrder++, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('copyrighting', 'Копирайтер (знак/₽)', $colOrder++, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
            new TableReportColumnData('seo-links', 'SEO-ссылки (₽)', $colOrder++, tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'),
        ]);

        // Добавляем столбцы для ставок с включенным параметром "Собирать статистику по отработанному времени?"
        if ($rates->isNotEmpty()) {
            foreach ($rates as $rate) {
                $field = 'position_' . $rate->id;
                $instance->columns->add(new TableReportColumnData($field, $rate->name, $colOrder++, component: 'position', tooltip: 'Автоматический съем данных происходит каждый понедельник в 05:00 и каждое 1-ое число месяца в 05:30. Если нужно обновить данные сейчас - кликните на ячейку и данные обновятся'));
            }
        }

        // сумма по должностям и программингу, копирайтеру и ссылкам
        $instance->columns->add(new TableReportColumnData('summary-spendings', 'Расходы итого (₽)', $colOrder++));
        $instance->columns->add(new TableReportColumnData('direct-budget', 'Остаток бюджета в Директе (₽)', $colOrder++, tooltip: 'Остаток на сейчас. Обновление — иконка обновления данных в шапке отчёта (лимит API: не чаще раза в 5 минут, не более 3 раз подряд, затем пауза 60 минут)'));
        $instance->columns->add(new TableReportColumnData('direct-spendings', 'Расход в Директе (₽)', $colOrder++, tooltip: 'Расход за выбранный период из базы Касини. Обновление — иконка обновления данных в шапке отчёта (лимит API: не чаще раза в 5 минут, не более 3 раз подряд, затем пауза 60 минут)'));

        return $instance;
    }

    /**
     * Загрузка сохранённых настроек с поддержкой legacy без dateFrom.
     * Динамические колонки position_* пересобираются под актуальный справочник ставок;
     * видимость и порядок совпадающих колонок сохраняются.
     *
     * Важно: имя НЕ должно начинаться с from* — Spatie Laravel Data
     * считает такие методы «магическими» конструкторами и уходит в рекурсию.
     *
     * @param  array<string, mixed>|string  $settings
     * @param  array<int, object>|Collection<int, object>  $rates
     */
    public static function hydrateFromSavedSettings(array|string $settings, array|Collection $rates = []): self
    {
        if (is_array($rates)) {
            $rates = new Collection($rates);
        }

        $payload = is_string($settings) ? json_decode($settings, true) : $settings;
        if (! is_array($payload)) {
            $payload = [];
        }

        if (! array_key_exists('dateFrom', $payload) && array_key_exists('dateTo', $payload)) {
            $payload['dateFrom'] = $payload['dateTo'];
        }

        if (! array_key_exists('dateFrom', $payload) && ! array_key_exists('dateTo', $payload)) {
            $current = Carbon::now()->startOfMonth()->toIso8601String();
            $payload['dateFrom'] = $current;
            $payload['dateTo'] = $current;
        }

        $saved = self::from($payload);
        $saved->clampPeriodToPresent();

        $rebuilt = self::create($rates);
        $rebuilt->grouping = $saved->grouping;
        if (in_array($rebuilt->grouping, [ChannelReportGrouping::ROLE, ChannelReportGrouping::TOOLS], true)) {
            // Группировки «по ролям» и «по инструментам» убрали из UI.
            $rebuilt->grouping = ChannelReportGrouping::NONE;
        }
        $rebuilt->dateFrom = $saved->dateFrom;
        $rebuilt->dateTo = $saved->dateTo;
        $rebuilt->showInactive = $saved->showInactive;
        $rebuilt->includeVat = $saved->includeVat;
        $rebuilt->applySavedColumnPreferences($saved->columns);
        $rebuilt->syncCanonicalColumnTooltips($rates);

        return $rebuilt;
    }

    /**
     * Перенести isVisible/order с сохранённых колонок на актуальный набор.
     *
     * @param  Collection<int, TableReportColumnData>  $savedColumns
     */
    public function applySavedColumnPreferences(Collection $savedColumns): void
    {
        if ($savedColumns->isEmpty()) {
            return;
        }

        $prefs = $savedColumns->keyBy(fn (TableReportColumnData $column) => $column->field);

        $samePositionSchema = self::positionColumnFields($savedColumns)
            === self::positionColumnFields($this->columns);

        foreach ($this->columns as $column) {
            $saved = $prefs->get($column->field);
            if ($saved === null) {
                continue;
            }

            $column->isVisible = $saved->isVisible;

            if ($samePositionSchema) {
                $column->order = $saved->order;
            }
        }

        if ($samePositionSchema) {
            $this->columns = $this->columns
                ->sortBy(fn (TableReportColumnData $column) => $column->order)
                ->values();
        }
    }

    /**
     * @param  Collection<int, TableReportColumnData>  $columns
     * @return list<string>
     */
    private static function positionColumnFields(Collection $columns): array
    {
        return $columns
            ->filter(fn (TableReportColumnData $column) => str_starts_with($column->field, 'position_'))
            ->pluck('field')
            ->values()
            ->all();
    }

    /**
     * Подтянуть актуальные tooltip из create(), чтобы правки в коде доходили
     * до пользователей с уже сохранёнными настройками колонок.
     *
     * @param  array<int, object>|Collection<int, object>  $rates
     */
    public function syncCanonicalColumnTooltips(array|Collection $rates = []): void
    {
        if (is_array($rates)) {
            $rates = new Collection($rates);
        }

        $defaults = self::create($rates)->columns->keyBy(fn (TableReportColumnData $column) => $column->field);

        foreach ($this->columns as $column) {
            $default = $defaults->get($column->field);
            if ($default !== null) {
                $column->tooltip = $default->tooltip;
            }
        }
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
