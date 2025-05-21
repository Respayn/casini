<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class YandexMetrikaAuthService
{
    public function getAccessToken(string $code): array
    {
        $client = new Client([
            'base_uri' => 'https://oauth.yandex.ru/',
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'timeout' => 15
        ]);

        try {
            $response = $client->post('token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => config('services.yandex_metrika.client_id'),
                    'client_secret' => config('services.yandex_metrika.client_secret'),
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'oauth_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'],
                'counter_id' => $this->getCounterId($data['access_token'])
            ];

        } catch (GuzzleException $e) {
            throw new \RuntimeException('Yandex Metrika auth failed: '.$e->getMessage());
        }
    }

    private function getCounterId(string $token): int
    {
        $response = (new Client())->get('https://api-metrika.yandex.net/management/v1/counters', [
            'headers' => ['Authorization' => "OAuth $token"]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['counters'][0]['id'] ?? 0;
    }
}
