<?php

namespace App\Contracts;

use Carbon\Carbon;

interface YandexDirectClientInterface
{
    /**
     * Получение баланса аккаунта
     */
    public function getAccountBalance(): float;

    /**
     * Запрос отчета
     */
    public function requestReport(array $params): array;

    /**
     * Базовый метод для API-запросов
     */
    public function request(string $method, string $endpoint, array $params = [], array $headers = []): array;

    /**
     * Получение списка кампаний
     */
    public function getCampaigns(array $fields = ['Id', 'Name']): array;

    /**
     * Получение статистики по кампании
     */
    public function getCampaignStatistics(int $campaignId, Carbon $startDate, Carbon $endDate): array;
}
