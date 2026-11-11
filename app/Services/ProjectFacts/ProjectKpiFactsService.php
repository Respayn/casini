<?php

namespace App\Services\ProjectFacts;

use App\Clients\Callibri\Filters\UtmFilter;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Factories\CallibriFilterFactory;
use App\Models\CallibriDailyLeadCount;
use App\Models\CallibriLead;
use App\Models\YandexDirectCampaignStats;
use App\Models\YandexDirectDailySpending;
use App\Models\YandexMetrikaGoalDirectSummary;
use App\Models\YandexMetrikaGoalUtm;
use App\Models\YandexMetrikaSearchEnginesStats;
use App\Models\YandexSearchApiDailyTopPercent;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use Illuminate\Support\Carbon;

/**
 * Общие факты KPI для Каналов / Статистики / отчётов.
 */
class ProjectKpiFactsService
{
    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly CallibriFilterFactory $callibriFilterFactory,
    ) {}

    /**
     * Расход Директа за период (как в Каналах и Статистике).
     */
    public function directBudget(
        int $projectId,
        Carbon $from,
        Carbon $to,
        bool $includeVat = true,
    ): ?float {
        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return null;
        }

        $column = $includeVat ? 'cost_with_vat' : 'cost_without_vat';
        $rows = YandexDirectDailySpending::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', $column]);

        if ($rows->isEmpty()) {
            return null;
        }

        return round((float) $rows->sum($column), 2);
    }

    /**
     * Лиды по дням: Callibri (после фильтров) + цели UTM Метрики, если интеграции включены.
     *
     * @return array<string, int> Y-m-d => count
     */
    public function leadsByDay(int $projectId, Carbon $from, Carbon $to): array
    {
        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return [];
        }

        $map = [];

        if ($this->credentials->hasEnabled($projectId, 'callibri')) {
            foreach ($this->filteredCallibriLeads($projectId, $from, $to) as $lead) {
                $day = Carbon::parse((string) $lead->date)->toDateString();
                $map[$day] = ($map[$day] ?? 0) + 1;
            }
        }

        if ($this->credentials->hasEnabled($projectId, 'yandex_metrika')) {
            $settings = $this->credentials->settingsFor($projectId, 'yandex_metrika') ?? [];
            $countSource = YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($settings);

            if ($countSource === YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_DIRECT_SUMMARY) {
                foreach ($this->directSummaryGoalsByMonth($projectId, $from, $to) as $month => $conversions) {
                    $day = Carbon::parse($month)->endOfMonth();
                    if ($day->greaterThan($to)) {
                        $day = $to->copy();
                    }
                    if ($day->lessThan($from)) {
                        $day = $from->copy();
                    }
                    $key = $day->toDateString();
                    $map[$key] = ($map[$key] ?? 0) + $conversions;
                }
            } else {
                foreach ($this->filteredMetrikaUtmGoals($projectId, $from, $to) as $goal) {
                    $day = $goal->achieved_date instanceof Carbon
                        ? $goal->achieved_date->toDateString()
                        : Carbon::parse((string) $goal->achieved_date)->toDateString();
                    $map[$day] = ($map[$day] ?? 0) + 1;
                }
            }
        }

        if ($map === []
            && ! $this->credentials->hasEnabled($projectId, 'callibri')
            && ! $this->credentials->hasEnabled($projectId, 'yandex_metrika')
        ) {
            return [];
        }

        // Нулевые дни тоже пишем: в Статистике «Итог» требует срез за последний день месяца.
        if ($this->credentials->hasEnabled($projectId, 'callibri')
            || $this->credentials->hasEnabled($projectId, 'yandex_metrika')
        ) {
            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $key = $day->toDateString();
                if (! array_key_exists($key, $map)) {
                    $map[$key] = 0;
                }
            }
        }

        ksort($map);

        return $map;
    }

    public function leadsTotal(int $projectId, Carbon $from, Carbon $to): int
    {
        return array_sum($this->leadsByDay($projectId, $from, $to));
    }

    /**
     * Клики Директа по дням (визиты контекста).
     *
     * @return array<string, int>
     */
    public function directClicksByDay(int $projectId, Carbon $from, Carbon $to): array
    {
        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return [];
        }

        $rows = YandexDirectCampaignStats::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'clicks']);

        $map = [];
        foreach ($rows as $row) {
            $day = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : Carbon::parse((string) $row->date)->toDateString();
            $map[$day] = ($map[$day] ?? 0) + (int) $row->clicks;
        }

        ksort($map);

        return $map;
    }

    /**
     * Средний % в топ-10 Search API за период (как факт Статистики).
     */
    public function top10Average(int $projectId, Carbon $from, Carbon $to): ?float
    {
        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return null;
        }

        $rows = YandexSearchApiDailyTopPercent::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['top_10_percent']);

        if ($rows->isEmpty()) {
            return null;
        }

        return round((float) $rows->avg('top_10_percent'), 2);
    }

    /**
     * @return array<string, float> Y-m-d => %
     */
    public function top10ByDay(int $projectId, Carbon $from, Carbon $to): array
    {
        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return [];
        }

        $rows = YandexSearchApiDailyTopPercent::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'top_10_percent']);

        $map = [];
        foreach ($rows as $row) {
            $day = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : Carbon::parse((string) $row->date)->toDateString();
            $map[$day] = (float) $row->top_10_percent;
        }

        ksort($map);

        return $map;
    }

    /**
     * Визиты Метрики «Поисковые системы» за месяцы, пересекающие период.
     */
    public function seoSearchVisits(int $projectId, Carbon $from, Carbon $to): ?int
    {
        $sum = $this->seoSearchEnginesSum($projectId, $from, $to, 'visits');

        return $sum;
    }

    /**
     * Конверсии Метрики «Поисковые системы» за месяцы, пересекающие период.
     */
    public function seoSearchConversions(int $projectId, Carbon $from, Carbon $to): ?int
    {
        return $this->seoSearchEnginesSum($projectId, $from, $to, 'conversions');
    }

    /**
     * @return list<CallibriLead>
     */
    public function filteredCallibriLeads(int $projectId, Carbon $from, Carbon $to): array
    {
        $settings = $this->credentials->settingsFor($projectId, 'callibri') ?? [];
        $leads = CallibriLead::query()
            ->where('project_id', $projectId)
            ->whereBetween('date', [
                $from->copy()->startOfDay()->toDateTimeString(),
                $to->copy()->endOfDay()->toDateTimeString(),
            ])
            ->get();

        if ($leads->isEmpty()) {
            return [];
        }

        $asArrays = $leads->map(fn (CallibriLead $lead) => [
            'id' => $lead->id,
            'utm_source' => $lead->utm_source,
            'utm_campaign' => $lead->utm_campaign,
            'utm_medium' => $lead->utm_medium,
            'utm_content' => $lead->utm_content,
            'utm_term' => $lead->utm_term,
            // type/status в БД нет: фильтры типа/стоимости уже применены при съёме
            'type' => $settings['appeals_type'][0] ?? 'call',
            'is_lid' => true,
            'status' => '',
            '_model' => $lead,
        ])->all();

        $filters = $this->callibriFilterFactory->createFromSettings($settings);
        foreach ($filters as $filter) {
            // AppealType / LeadCost без полей в БД не отсекают сохранённые лиды:
            // при съёме они уже применены. На чтении оставляем UTM.
            if ($filter instanceof UtmFilter) {
                $asArrays = array_values($filter->apply($asArrays));
            }
        }

        return array_map(
            static fn (array $row) => $row['_model'],
            $asArrays,
        );
    }

    /**
     * Цели UTM для общей статистики, когда источник числа - UTM-метки.
     *
     * @return list<YandexMetrikaGoalUtm>
     */
    public function filteredMetrikaUtmGoals(int $projectId, Carbon $from, Carbon $to): array
    {
        $settings = $this->credentials->settingsFor($projectId, 'yandex_metrika') ?? [];
        $reports = is_array($settings['reports'] ?? null) ? $settings['reports'] : [];
        if (! ($reports['goals_utm'] ?? false)) {
            return [];
        }

        if (YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($settings)
            !== YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_UTM
        ) {
            return [];
        }

        $goals = YandexMetrikaGoalUtm::query()
            ->where('project_id', $projectId)
            ->whereBetween('achieved_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $mode = (string) ($settings['utm_filter_mode'] ?? 'none');
        if ($mode === 'none' || $mode === '') {
            return $goals->all();
        }

        $pattern = trim((string) ($settings['utm_'.$mode] ?? ''));
        $field = match ($mode) {
            'campaign' => 'utm_campaign',
            'medium' => 'utm_medium',
            default => 'utm_source',
        };

        return $goals->filter(function (YandexMetrikaGoalUtm $goal) use ($field, $pattern) {
            $value = trim((string) ($goal->{$field} ?? ''));
            if ($pattern === '') {
                return $value !== '';
            }

            foreach (array_map('trim', explode(',', $pattern)) as $piece) {
                if ($piece !== '' && str_contains(mb_strtolower($value), mb_strtolower($piece))) {
                    return true;
                }
            }

            return false;
        })->values()->all();
    }

    /**
     * Сумма целей «Директ, сводка» за месяцы периода. Без разреза по кампаниям.
     */
    public function directSummaryGoalsTotal(int $projectId, Carbon $from, Carbon $to): ?int
    {
        $byMonth = $this->directSummaryGoalsByMonth($projectId, $from, $to);
        if ($byMonth === []) {
            return null;
        }

        return (int) array_sum($byMonth);
    }

    /**
     * @return array<string, int> Y-m-01 => conversions
     */
    public function directSummaryGoalsByMonth(int $projectId, Carbon $from, Carbon $to): array
    {
        $settings = $this->credentials->settingsFor($projectId, 'yandex_metrika') ?? [];
        $reports = is_array($settings['reports'] ?? null) ? $settings['reports'] : [];
        if (! ($reports['goals_direct_summary'] ?? false)) {
            return [];
        }

        if (YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($settings)
            !== YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_DIRECT_SUMMARY
        ) {
            return [];
        }

        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return [];
        }

        $monthStart = $from->copy()->startOfMonth()->toDateString();
        $monthEnd = $to->copy()->startOfMonth()->toDateString();

        $rows = YandexMetrikaGoalDirectSummary::query()
            ->where('project_id', $projectId)
            ->whereBetween('month', [$monthStart, $monthEnd])
            ->get(['month', 'conversions']);

        if ($rows->isEmpty()) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $month = $row->month instanceof Carbon
                ? $row->month->copy()->startOfMonth()->toDateString()
                : Carbon::parse((string) $row->month)->startOfMonth()->toDateString();
            $map[$month] = ($map[$month] ?? 0) + (int) $row->conversions;
        }

        return $map;
    }

    /**
     * Пересчитать дневные счётчики Callibri из сохранённых лидов (после фильтров UTM).
     */
    public function recountCallibriDailyCounts(int $projectId, Carbon $from, Carbon $to): void
    {
        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return;
        }

        $byDay = [];
        foreach ($this->filteredCallibriLeads($projectId, $from, $to) as $lead) {
            $day = Carbon::parse((string) $lead->date)->toDateString();
            $byDay[$day] = ($byDay[$day] ?? 0) + 1;
        }

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            CallibriDailyLeadCount::query()->updateOrCreate(
                [
                    'project_id' => $projectId,
                    'date' => $key,
                ],
                [
                    'leads_count' => $byDay[$key] ?? 0,
                ],
            );
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function clampPeriod(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $today = Carbon::today();
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        return [$from, $to];
    }

    private function seoSearchEnginesSum(int $projectId, Carbon $from, Carbon $to, string $column): ?int
    {
        $monthStart = $from->copy()->startOfMonth()->toDateString();
        $monthEnd = $to->copy()->startOfMonth()->toDateString();

        $rows = YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $projectId)
            ->whereBetween('month', [$monthStart, $monthEnd])
            ->get([$column]);

        if ($rows->isEmpty()) {
            return null;
        }

        return (int) $rows->sum($column);
    }
}
