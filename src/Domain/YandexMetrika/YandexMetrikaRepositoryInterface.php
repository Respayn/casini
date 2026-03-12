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
}
