<?php

namespace App\Factories;

use App\Contracts\YandexDirectClientInterface;
use App\Clients\YandexDirect\YandexDirectClient;
use App\Clients\YandexDirect\YandexDirectV4Client;

class YandexDirectClientFactory
{
    /**
     * Создает клиент для работы с API Яндекс.Директ
     *
     * @param string $version Версия API (v4 или v5)
     * @param string $token OAuth-токен
     * @param string $clientLogin Логин клиента
     * @param bool $sandboxMode Режим песочницы
     */
    public static function create(
        string $token,
        string $clientLogin,
        string $version = 'v5',
        bool $sandboxMode = false
    ): YandexDirectClientInterface
    {
        return match(strtolower($version)) {
            'v4' => new YandexDirectV4Client($token, $clientLogin),
            'v5' => new YandexDirectClient($token, $clientLogin),
            default => throw new \InvalidArgumentException("Unsupported API version: $version")
        };
    }
}
