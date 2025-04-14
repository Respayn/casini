<?php

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class YandexDirectApiClient
{
    protected $client;
    protected $token;
    protected $clientLogin;
    protected $apiUrl;

    public function __construct()
    {
        $this->token = config('services.yandex_direct.token');
        $this->clientLogin = config('services.yandex_direct.client_login');
        $this->apiUrl = config('services.yandex_direct.api_url');

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept-Language' => 'ru',
                'Client-Login' => $this->clientLogin,
                'Content-Type' => 'application/json; charset=utf-8',
            ],
        ]);
    }

    public function sendRequest(string $service, string $method, array $params = [])
    {
        $url = $this->apiUrl . '/' . $service;

        $body = [
            'method' => $method,
            'params' => $params,
        ];

        try {
            $response = $this->client->post($url, [
                'json' => $body,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            // Обработка ошибок
            throw new \Exception('Yandex Direct API request failed: ' . $e->getMessage());
        }
    }
}
