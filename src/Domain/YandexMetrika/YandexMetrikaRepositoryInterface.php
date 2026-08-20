<?php

namespace Src\Domain\YandexMetrika;

use Src\Domain\ValueObjects\DateTimeRange;

interface YandexMetrikaRepositoryInterface
{
    /**
     * Получает статистику по поисковым системам за период.
     *
     * @param int $projectId
     * @param DateTimeRange $period
     * @return YandexMetrikaSearchEnginesStats[]
     */
    public function getSearchEnginesStats(int $projectId, DateTimeRange $period): array;

    /**
     * Получает данные о достижении целей с UTM-метками.
     *
     * @param int $projectId
     * @param DateTimeRange $period
     * @return YandexMetrikaGoalUtm[]
     */
    public function getGoalUtmStats(int $projectId, DateTimeRange $period): array;

    /**
     * Получает статистику достижений целей по месяцам из отчёта "Конверсии".
     *
     * @param int $projectId
     * @param DateTimeRange $period
     * @return YandexMetrikaGoalConversion[]
     */
    public function getGoalConversionsStats(int $projectId, DateTimeRange $period): array;

    /**
     * Получает статистику визитов по географии.
     *
     * @param int $projectId
     * @param DateTimeRange $period
     * @return YandexMetrikaVisitsGeo[]
     */
    public function getVisitsGeoStats(int $projectId, DateTimeRange $period): array;

    /**
     * Получает статистику визитов по поисковым запросам.
     *
     * @param int $projectId
     * @param DateTimeRange $period
     * @return YandexMetrikaVisitsSearchQueries[]
     */
    public function getVisitsSearchQueriesStats(int $projectId, DateTimeRange $period): array;

    /**
     * Сохраняет конверсии из отчёта «Поисковые системы», не затирая визиты.
     */
    public function upsertSearchEnginesConversions(int $projectId, string $searchEngine, string $month, int $conversions): void;

    /**
     * Сохраняет визиты/переходы из отчёта «Поисковые системы», не затирая конверсии.
     */
    public function upsertSearchEnginesVisits(int $projectId, string $searchEngine, string $month, int $visits): void;

    /**
     * Заменяет строки UTM-целей за период: удаляет старые и вставляет свежие.
     *
     * @param list<array{goal_name: string, achieved_date: string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_content: ?string, utm_term: ?string}> $rows
     */
    public function replaceGoalUtmRows(int $projectId, string $dateFrom, string $dateTo, array $rows): void;

    /**
     * Upsert строк конверсий по unique (project_id, goal_name, month).
     *
     * @param list<array{goal_name: string, month: string, conversions: int}> $rows
     */
    public function upsertGoalConversions(int $projectId, array $rows): void;

    /**
     * @param int $projectId
     * @param DateTimeRange $period
     * @return YandexMetrikaGoalDirectSummary[]
     */
    public function getGoalDirectSummaryStats(int $projectId, DateTimeRange $period): array;

    /**
     * Upsert строк «Директ, сводка» по unique (project_id, goal_name, month).
     *
     * @param list<array{goal_name: string, month: string, conversions: int}> $rows
     */
    public function upsertGoalDirectSummary(int $projectId, array $rows): void;
}
