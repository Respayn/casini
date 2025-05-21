<?php

namespace App\Factories;

use App\Clients\YandexMetrika\YandexMetrikaClient;
use App\Contracts\YandexMetrikaClientInterface;

class YandexMetrikaClientFactory
{
    /**
     * Создает клиент для работы с API Яндекс.Метрики
     */
    public function create(
        string $token,
        string $clientLogin,
        ?int $counterId = null,
        string $apiVersion = 'v1'
    ): YandexMetrikaClientInterface {
        return match(strtolower($apiVersion)) {
            'v1' => new YandexMetrikaClient($token, $clientLogin, $counterId),
            default => throw new \InvalidArgumentException("Unsupported API version: $apiVersion")
        };
    }
}
