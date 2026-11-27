<?php

namespace Src\Domain\YandexMetrika;

use Src\Domain\ValueObjects\DateTimeRange;

interface YandexMetrikaRepositoryInterface
{
    /**
     * Получает статистику по поисковым системам за период.
     *
     * @return YandexMetrikaSearchEnginesStats[]
     */
    public function getSearchEnginesStats(int $projectId, DateTimeRange $period): array;

    /**
     * Статистика поисковых систем для списка проектов.
     *
     * @param  list<int>  $projectIds
     * @return YandexMetrikaSearchEnginesStats[]
     */
    public function getSearchEnginesStatsForProjects(array $projectIds, DateTimeRange $period): array;

    /**
     * Получает данные о достижении целей с UTM-метками.
     *
     * @return YandexMetrikaGoalUtm[]
     */
    public function getGoalUtmStats(int $projectId, DateTimeRange $period): array;

    /**
     * Цели UTM для списка проектов.
     *
     * @param  list<int>  $projectIds
     * @return YandexMetrikaGoalUtm[]
     */
    public function getGoalUtmStatsForProjects(array $projectIds, DateTimeRange $period): array;

    /**
     * Получает статистику достижений целей по месяцам из отчёта "Конверсии".
     *
     * @return YandexMetrikaGoalConversion[]
     */
    public function getGoalConversionsStats(int $projectId, DateTimeRange $period): array;

    /**
     * Получает статистику визитов по географии.
     *
     * @return YandexMetrikaVisitsGeo[]
     */
    public function getVisitsGeoStats(int $projectId, DateTimeRange $period): array;

    /**
     * Получает статистику визитов по поисковым запросам.
     *
     * @return YandexMetrikaVisitsSearchQueries[]
     */
    public function getVisitsSearchQueriesStats(int $projectId, DateTimeRange $period): array;

    /**
     * Получает достижения выбранных целей по поисковым системам за период.
     *
     * @return YandexMetrikaSearchEngineGoal[]
     */
    public function getSearchEngineGoals(int $projectId, DateTimeRange $period): array;

    /**
     * Обнуляет конверсии «Поисковые системы» в диапазоне дат (колонка month = день).
     */
    public function zeroSearchEnginesConversionsInRange(int $projectId, string $dateFrom, string $dateTo): void;

    /**
     * Обнуляет визиты «Поисковые системы» в диапазоне дат (колонка month = день).
     */
    public function zeroSearchEnginesVisitsInRange(int $projectId, string $dateFrom, string $dateTo): void;

    /**
     * Удаляет строки «Директ, сводка» в диапазоне дат (колонка month = день).
     */
    public function deleteGoalDirectSummaryInRange(int $projectId, string $dateFrom, string $dateTo): void;

    /**
     * Сохраняет конверсии из отчёта «Поисковые системы», не затирая визиты.
     * $month — календарный день Y-m-d.
     */
    public function upsertSearchEnginesConversions(int $projectId, string $searchEngine, string $month, int $conversions): void;

    /**
     * Сохраняет число одной цели по поисковику за месяц.
     */
    public function upsertSearchEngineGoal(
        int $projectId,
        string $searchEngine,
        int $goalId,
        string $goalName,
        string $month,
        int $conversions
    ): void;

    /**
     * Сохраняет визиты/переходы из отчёта «Поисковые системы», не затирая конверсии.
     * $month — календарный день Y-m-d.
     */
    public function upsertSearchEnginesVisits(int $projectId, string $searchEngine, string $month, int $visits): void;

    /**
     * Сохраняет визиты или посетителей из отчёта «Поисковые запросы».
     * Обновляет только выбранную метрику, не затирая вторую и goal_reaches.
     *
     * @param  'visits'|'users'  $visitsMetric
     */
    public function upsertSearchQueriesVisits(
        int $projectId,
        string $phrase,
        string $month,
        string $visitsMetric,
        int $value
    ): void;

    /**
     * Сохраняет визиты или посетителей из отчёта «География».
     * Обновляет только выбранную метрику, не затирая вторую и goal_reaches.
     *
     * @param  'visits'|'users'  $visitsMetric
     */
    public function upsertVisitsGeo(
        int $projectId,
        string $city,
        string $month,
        string $visitsMetric,
        int $value
    ): void;

    /**
     * Заменяет строки UTM-целей за период: удаляет старые и вставляет свежие.
     *
     * @param  list<array{goal_name: string, achieved_date: string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_content: ?string, utm_term: ?string}>  $rows
     */
    public function replaceGoalUtmRows(int $projectId, string $dateFrom, string $dateTo, array $rows): void;

    /**
     * Upsert строк конверсий по unique (project_id, goal_name, month).
     *
     * @param  list<array{goal_name: string, month: string, conversions: int}>  $rows
     */
    public function upsertGoalConversions(int $projectId, array $rows): void;

    /**
     * @return YandexMetrikaGoalDirectSummary[]
     */
    public function getGoalDirectSummaryStats(int $projectId, DateTimeRange $period): array;

    /**
     * «Директ, сводка» для списка проектов.
     *
     * @param  list<int>  $projectIds
     * @return YandexMetrikaGoalDirectSummary[]
     */
    public function getGoalDirectSummaryStatsForProjects(array $projectIds, DateTimeRange $period): array;

    /**
     * Upsert строк «Директ, сводка» по unique (project_id, goal_name, month).
     *
     * @param  list<array{goal_name: string, month: string, conversions: int}>  $rows
     */
    public function upsertGoalDirectSummary(int $projectId, array $rows): void;

    /**
     * @return YandexMetrikaGoalDirectCampaign[]
     */
    public function getGoalDirectCampaignStats(int $projectId, DateTimeRange $period): array;

    /**
     * Upsert строк «Директ, сводка» по кампаниям: unique (project_id, goal_name, campaign_id, month).
     *
     * @param  list<array{goal_name: string, campaign_id: int, campaign_name: string, month: string, conversions: int}>  $rows
     */
    public function upsertGoalDirectCampaigns(int $projectId, array $rows): void;
}
