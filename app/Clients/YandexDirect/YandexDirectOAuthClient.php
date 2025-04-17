<?php

namespace App\Clients\YandexDirect;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class YandexDirectOAuthClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://oauth.yandex.ru/',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
    }

    public function getAccessToken(
        string $clientId,
        string $clientSecret,
        string $code,
        string $redirectUri
    ): array
    {
        try {
            $response = $this->client->post('https://oauth.yandex.ru/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to get access token: ' . $e->getMessage());
        }
    }

    public function refreshToken(
        string $clientId,
        string $clientSecret,
        string $refreshToken
    ): array
    {
        try {
            $response = $this->client->post('token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to refresh token: ' . $e->getMessage());
        }
    }
}
