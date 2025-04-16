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
        string $version,
        string $token,
        string $clientLogin,
        bool $sandboxMode = false
    ): YandexDirectClientInterface
    {
        return match(strtolower($version)) {
            'v4' => new YandexDirectV4Client($token, $clientLogin, $sandboxMode),
            'v5' => new YandexDirectClient($token, $clientLogin, $sandboxMode),
            default => throw new \InvalidArgumentException("Unsupported API version: $version")
        };
    }
}
