<?php

namespace App\Contracts;

use GuzzleHttp\Exception\GuzzleException;

interface YandexMetrikaClientInterface
{
    /**
     * Базовый метод для выполнения запросов к API
     *
     * @throws \Exception
     * @throws GuzzleException
     */
    public function request(
        string $method,
        string $endpoint,
        array $params = []
    ): array;

    /**
     * Получить список целей счетчика
     *
     * @return array [
     *     'goals' => array,
     *     'total' => int
     * ]
     */
    public function getGoals(): array;

    /**
     * Получить отчет по визитам
     *
     * @param array $params Параметры запроса:
     * [
     *     'date1' => string,
     *     'date2' => string,
     *     'metrics' => string,
     *     'dimensions' => string,
     *     ...
     * ]
     */
    public function getVisitsReport(array $params): array;

    /**
     * Получить данные по достижениям цели
     *
     * @param int $goalId ID цели
     * @param array $params Дополнительные параметры
     */
    public function getGoalAchievements(int $goalId, array $params = []): array;
}
