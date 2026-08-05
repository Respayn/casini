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

    /**
     * Выделять клиенто-проекты с невыполненными KPI (UI настроек отчёта).
     */
    public string $highlightUnmetKpi = 'Y';

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
            new TableReportColumnData('project-type', 'Тип клиенто-проекта', $colOrder++),
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
            foreach ($instance->detailMonths() as $index => $month) {
                $label = DateTimeHelper::getMonthName($month->month).' '.$month->format('Y');
                $instance->columns->add(new TableReportColumnData(
                    "month_{$index}",
                    $label,
                    $colOrder++,
                    component: 'fact',
                    isSortable: false,
                ));
            }
        }

        $instance->columns->add(new TableReportColumnData('summary', 'Итог', $colOrder++));
        $instance->columns->add(new TableReportColumnData('prediction', 'Прогноз', $colOrder++));
        $instance->columns->add(new TableReportColumnData('bonuses', 'Бонусы и гарантии', $colOrder++));

        return $instance;
    }

    /**
     * Восстановить настройки пользователя из БД.
     * Динамические колонки день/неделя/месяц пересобираются под актуальный период;
     * видимость и порядок совпадающих колонок сохраняются.
     */
    public static function hydrateFromSavedSettings(array|string $settings): self
    {
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

        $rebuilt = self::create(
            $saved->detailLevel,
            $saved->dateFrom,
            $saved->dateTo,
        );
        $rebuilt->grouping = $saved->grouping;
        if ($rebuilt->grouping === ChannelReportGrouping::TOOLS) {
            // В Статистике группировку «по инструментам» убрали из UI.
            $rebuilt->grouping = ChannelReportGrouping::NONE;
        }
        $rebuilt->showInactive = $saved->showInactive;
        $rebuilt->includeVat = $saved->includeVat;
        $rebuilt->accumulateData = $saved->accumulateData;
        $rebuilt->highlightUnmetKpi = $saved->highlightUnmetKpi ?: 'Y';
        $rebuilt->applySavedColumnPreferences($saved->columns);

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

        $savedFactFields = $savedColumns
            ->filter(fn (TableReportColumnData $column) => $column->component === 'fact')
            ->pluck('field')
            ->values()
            ->all();
        $currentFactFields = $this->columns
            ->filter(fn (TableReportColumnData $column) => $column->component === 'fact')
            ->pluck('field')
            ->values()
            ->all();
        $sameFactSchema = $savedFactFields === $currentFactFields;

        foreach ($this->columns as $column) {
            $saved = $prefs->get($column->field);
            if ($saved === null) {
                continue;
            }

            $column->isVisible = $saved->isVisible;
            if ($sameFactSchema) {
                $column->order = $saved->order;
            }
        }

        if ($sameFactSchema) {
            $this->columns = $this->columns
                ->sortBy(fn (TableReportColumnData $column) => $column->order)
                ->values();
        }
    }

    /**
     * Месяцы периода для детализации «по месяцам»: dateFrom…dateTo включительно.
     *
     * @return list<Carbon>
     */
    public function detailMonths(): array
    {
        // Обход через year/month, без Carbon::addMonth()/lte — на Wireable-инстансах
        // Livewire addMonth+lte давали только первый месяц при корректных dateFrom/dateTo.
        $months = [];
        $year = (int) $this->dateFrom->format('Y');
        $month = (int) $this->dateFrom->format('n');
        $endYear = (int) $this->dateTo->format('Y');
        $endMonth = (int) $this->dateTo->format('n');

        while ($year < $endYear || ($year === $endYear && $month <= $endMonth)) {
            $months[] = Carbon::create($year, $month, 1)->startOfDay();
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }

        return $months;
    }

    /**
     * Месяц для колонок детализации день/неделя: один месяц периода либо dateTo при интервале.
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
