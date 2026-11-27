<?php

namespace App\Services\ProjectFacts;

use App\Clients\Callibri\Filters\UtmFilter;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Factories\CallibriFilterFactory;
use App\Models\CallibriLead;
use App\Repositories\CallibriLeadRepository;
use App\Repositories\YandexSearchApiDailyTopPercentRepository;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectSummary;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaSearchEnginesStats;

/**
 * Общие факты KPI для Каналов / Статистики / отчётов.
 */
class ProjectKpiFactsService
{
    public function __construct(
        private readonly IntegrationProjectCredentials $credentials,
        private readonly CallibriFilterFactory $callibriFilterFactory,
        private readonly YandexDirectRepositoryInterface $yandexDirect,
        private readonly CallibriLeadRepository $callibriLeads,
        private readonly YandexMetrikaRepositoryInterface $yandexMetrika,
        private readonly YandexSearchApiDailyTopPercentRepository $searchApiTopPercents,
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

        return $this->yandexDirect->sumDailySpendings(
            $projectId,
            $from->toDateString(),
            $to->toDateString(),
            $includeVat,
        );
    }

    /**
     * Лиды по дням: Callibri (после фильтров) + цели UTM Метрики, если интеграции включены.
     *
     * @return array<string, int> Y-m-d => count
     */
    public function leadsByDay(int $projectId, Carbon $from, Carbon $to): array
    {
        return $this->leadsByDayForProjects([$projectId], $from, $to)[$projectId] ?? [];
    }

    /**
     * Клики Директа по дням (визиты контекста).
     *
     * @return array<string, int>
     */
    public function directClicksByDay(int $projectId, Carbon $from, Carbon $to): array
    {
        return $this->directClicksByDayForProjects([$projectId], $from, $to)[$projectId] ?? [];
    }

    /**
     * Клики Директа по дням для списка проектов.
     *
     * @param  list<int>  $projectIds
     * @return array<int, array<string, int>>
     */
    public function directClicksByDayForProjects(array $projectIds, Carbon $from, Carbon $to): array
    {
        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to) || $projectIds === []) {
            return [];
        }

        return $this->yandexDirect->clicksByDayForProjects(
            $projectIds,
            $from->toDateString(),
            $to->toDateString(),
        );
    }

    /**
     * Лиды по дням для списка проектов одним проходом по БД.
     *
     * @param  list<int>  $projectIds
     * @return array<int, array<string, int>>
     */
    public function leadsByDayForProjects(array $projectIds, Carbon $from, Carbon $to): array
    {
        [$from, $to] = $this->clampPeriod($from, $to);
        $projectIds = array_values(array_unique(array_map('intval', $projectIds)));
        if ($from->greaterThan($to) || $projectIds === []) {
            return [];
        }

        $integrations = $this->credentials->mapByProjects($projectIds);
        $callibriProjectIds = [];
        $metrikaProjectIds = [];
        foreach ($projectIds as $projectId) {
            if ($this->credentials->integrationFromMap($integrations, $projectId, 'callibri') !== null) {
                $callibriProjectIds[] = $projectId;
            }
            if ($this->credentials->integrationFromMap($integrations, $projectId, 'yandex_metrika') !== null) {
                $metrikaProjectIds[] = $projectId;
            }
        }

        $leadsByProject = $callibriProjectIds === []
            ? collect()
            : $this->callibriLeads->getLeadsBetweenForProjects(
                $callibriProjectIds,
                $from->copy()->startOfDay()->toDateTimeString(),
                $to->copy()->endOfDay()->toDateTimeString(),
            )->groupBy(fn (CallibriLead $lead) => (int) $lead->project_id);

        $utmByProject = [];
        if ($metrikaProjectIds !== []) {
            foreach ($this->yandexMetrika->getGoalUtmStatsForProjects($metrikaProjectIds, $this->toRange($from, $to)) as $goal) {
                $utmByProject[$goal->getProjectId()][] = $goal;
            }
        }

        $directSummaryByProject = [];
        if ($metrikaProjectIds !== []) {
            foreach ($this->yandexMetrika->getGoalDirectSummaryStatsForProjects($metrikaProjectIds, $this->toRange($from, $to)) as $row) {
                $directSummaryByProject[$row->getProjectId()][] = $row;
            }
        }

        $result = [];
        foreach ($projectIds as $projectId) {
            $map = [];
            $callibriEnabled = $this->credentials->integrationFromMap($integrations, $projectId, 'callibri') !== null;
            $metrikaItem = $this->credentials->integrationFromMap($integrations, $projectId, 'yandex_metrika');
            $metrikaEnabled = $metrikaItem !== null;
            $settings = is_array($metrikaItem?->settings ?? null) ? $metrikaItem->settings : [];

            if ($callibriEnabled) {
                $callibriSettings = $this->credentials->integrationFromMap($integrations, $projectId, 'callibri');
                $callibriSettingsArray = is_array($callibriSettings?->settings ?? null) ? $callibriSettings->settings : [];
                /** @var Collection<int, CallibriLead> $projectLeads */
                $projectLeads = $leadsByProject->get($projectId, collect());
                foreach ($this->filterCallibriLeadModels($projectLeads, $callibriSettingsArray) as $lead) {
                    $day = Carbon::parse((string) $lead->date)->toDateString();
                    $map[$day] = ($map[$day] ?? 0) + 1;
                }
            }

            if ($metrikaEnabled) {
                $countSource = YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($settings);

                if ($countSource === YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_DIRECT_SUMMARY) {
                    foreach ($this->directSummaryGoalsByDayFromRows(
                        $settings,
                        $directSummaryByProject[$projectId] ?? [],
                    ) as $day => $conversions) {
                        $map[$day] = ($map[$day] ?? 0) + $conversions;
                    }
                } else {
                    foreach ($this->filterMetrikaUtmGoalsFromRows($settings, $utmByProject[$projectId] ?? []) as $goal) {
                        $day = $goal->getAchievedDate()->format('Y-m-d');
                        $map[$day] = ($map[$day] ?? 0) + 1;
                    }
                }
            }

            if ($map === [] && ! $callibriEnabled && ! $metrikaEnabled) {
                continue;
            }

            if ($callibriEnabled || $metrikaEnabled) {
                for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                    $key = $day->toDateString();
                    if (! array_key_exists($key, $map)) {
                        $map[$key] = 0;
                    }
                }
            }

            ksort($map);
            $result[$projectId] = $map;
        }

        return $result;
    }

    /**
     * Визиты / конверсии SEO по дням для списка проектов.
     *
     * @param  list<int>  $projectIds
     * @param  'visits'|'conversions'  $column
     * @return array<int, array<string, int>> project_id => (Y-m-d => value)
     */
    public function seoSearchEnginesByDayForProjects(
        array $projectIds,
        Carbon $from,
        Carbon $to,
        string $column,
    ): array {
        $projectIds = array_values(array_unique(array_map('intval', $projectIds)));
        if ($projectIds === []) {
            return [];
        }

        $rows = $this->yandexMetrika->getSearchEnginesStatsForProjects(
            $projectIds,
            $this->toRange($from, $to),
        );

        $map = [];
        foreach ($rows as $row) {
            /** @var YandexMetrikaSearchEnginesStats $row */
            $projectId = $row->getProjectId();
            $day = $row->getMonth()->format('Y-m-d');
            $value = $column === 'conversions' ? $row->getConversions() : $row->getVisits();
            $map[$projectId][$day] = ($map[$projectId][$day] ?? 0) + $value;
        }

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

        return $this->searchApiTopPercents->averageTop10Percent(
            $projectId,
            $from->toDateString(),
            $to->toDateString(),
        );
    }

    /**
     * Визиты Метрики «Поисковые системы» за месяцы, пересекающие период.
     */
    public function seoSearchVisits(int $projectId, Carbon $from, Carbon $to): ?int
    {
        return $this->seoSearchEnginesSum($projectId, $from, $to, 'visits');
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
        $leads = $this->callibriLeads->getLeadsBetween(
            $projectId,
            $from->copy()->startOfDay()->toDateTimeString(),
            $to->copy()->endOfDay()->toDateTimeString(),
        );

        return $this->filterCallibriLeadModels($leads, $settings);
    }

    /**
     * Цели UTM для общей статистики, когда источник числа - UTM-метки.
     *
     * @return list<YandexMetrikaGoalUtm>
     */
    public function filteredMetrikaUtmGoals(int $projectId, Carbon $from, Carbon $to): array
    {
        $settings = $this->credentials->settingsFor($projectId, 'yandex_metrika') ?? [];
        $goals = $this->yandexMetrika->getGoalUtmStats($projectId, $this->toRange($from, $to));

        return $this->filterMetrikaUtmGoalsFromRows($settings, $goals);
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
     * @return array<string, int> Y-m-01 => conversions (дни складываются в месяц)
     */
    public function directSummaryGoalsByMonth(int $projectId, Carbon $from, Carbon $to): array
    {
        $settings = $this->credentials->settingsFor($projectId, 'yandex_metrika') ?? [];

        [$from, $to] = $this->clampPeriod($from, $to);
        if ($from->greaterThan($to)) {
            return [];
        }

        $rows = $this->yandexMetrika->getGoalDirectSummaryStats(
            $projectId,
            $this->toRange($from, $to),
        );

        $byMonth = [];
        foreach ($this->directSummaryGoalsByDayFromRows($settings, $rows) as $day => $conversions) {
            $monthKey = Carbon::parse($day)->startOfMonth()->format('Y-m-01');
            $byMonth[$monthKey] = ($byMonth[$monthKey] ?? 0) + $conversions;
        }

        return $byMonth;
    }

    /**
     * @param  Collection<int, CallibriLead>  $leads
     * @param  array<string, mixed>  $settings
     * @return list<CallibriLead>
     */
    private function filterCallibriLeadModels(Collection $leads, array $settings): array
    {
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
     * @param  array<string, mixed>  $settings
     * @param  list<YandexMetrikaGoalUtm>  $goals
     * @return list<YandexMetrikaGoalUtm>
     */
    private function filterMetrikaUtmGoalsFromRows(array $settings, array $goals): array
    {
        $reports = is_array($settings['reports'] ?? null) ? $settings['reports'] : [];
        if (! ($reports['goals_utm'] ?? false)) {
            return [];
        }

        if (YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($settings)
            !== YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_UTM
        ) {
            return [];
        }

        $mode = (string) ($settings['utm_filter_mode'] ?? 'none');
        if ($mode === 'none' || $mode === '') {
            return $goals;
        }

        $pattern = trim((string) ($settings['utm_'.$mode] ?? ''));

        return array_values(array_filter(
            $goals,
            function (YandexMetrikaGoalUtm $goal) use ($mode, $pattern): bool {
                $value = trim((string) match ($mode) {
                    'campaign' => $goal->getUtmCampaign() ?? '',
                    'medium' => $goal->getUtmMedium() ?? '',
                    default => $goal->getUtmSource() ?? '',
                });

                if ($pattern === '') {
                    return $value !== '';
                }

                foreach (array_map('trim', explode(',', $pattern)) as $piece) {
                    if ($piece !== '' && str_contains(mb_strtolower($value), mb_strtolower($piece))) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<YandexMetrikaGoalDirectSummary>  $rows
     * @return array<string, int> Y-m-d => conversions
     */
    private function directSummaryGoalsByDayFromRows(array $settings, array $rows): array
    {
        $reports = is_array($settings['reports'] ?? null) ? $settings['reports'] : [];
        if (! ($reports['goals_direct_summary'] ?? false)) {
            return [];
        }

        if (YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource($settings)
            !== YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_DIRECT_SUMMARY
        ) {
            return [];
        }

        if ($rows === []) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $day = $row->getMonth()->format('Y-m-d');
            $map[$day] = ($map[$day] ?? 0) + $row->getConversions();
        }

        return $map;
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
        $rows = $this->yandexMetrika->getSearchEnginesStats(
            $projectId,
            $this->toRange($from, $to),
        );

        if ($rows === []) {
            return null;
        }

        $sum = 0;
        foreach ($rows as $row) {
            $sum += $column === 'conversions' ? $row->getConversions() : $row->getVisits();
        }

        return $sum;
    }

    private function toRange(Carbon $from, Carbon $to): DateTimeRange
    {
        return new DateTimeRange(
            new DateTimeImmutable($from->toDateString()),
            new DateTimeImmutable($to->toDateString()),
        );
    }

    private function toMonthRange(Carbon $from, Carbon $to): DateTimeRange
    {
        return new DateTimeRange(
            new DateTimeImmutable($from->copy()->startOfMonth()->toDateString()),
            new DateTimeImmutable($to->copy()->startOfMonth()->toDateString()),
        );
    }
}
