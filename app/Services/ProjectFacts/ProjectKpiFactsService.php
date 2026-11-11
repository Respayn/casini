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
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;

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
                    $day = $goal->getAchievedDate()->format('Y-m-d');
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

        return $this->yandexDirect->clicksByDay(
            $projectId,
            $from->toDateString(),
            $to->toDateString(),
        );
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

        $goals = $this->yandexMetrika->getGoalUtmStats($projectId, $this->toRange($from, $to));

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

        $rows = $this->yandexMetrika->getGoalDirectSummaryStats(
            $projectId,
            $this->toMonthRange($from, $to),
        );

        if ($rows === []) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $month = $row->getMonth()->format('Y-m-01');
            $map[$month] = ($map[$month] ?? 0) + $row->getConversions();
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
            $this->toMonthRange($from, $to),
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
