<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;

class YandexMetrikaAuthService
{
    /**
     * @return array{oauth_token: string, refresh_token: ?string, expires_in: mixed}
     */
    public function getAccessToken(string $code): array
    {
        $client = new Client([
            'base_uri' => 'https://oauth.yandex.ru/',
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'timeout' => 15,
        ]);

        try {
            $response = $client->post('token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => config('services.yandex_metrika.client_id'),
                    'client_secret' => config('services.yandex_metrika.client_secret'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'oauth_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'],
            ];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Yandex Metrika auth failed: '.$e->getMessage());
        }
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_in: mixed}
     */
    public function exchangeCode(string $code): array
    {
        $tokens = $this->getAccessToken($code);

        return [
            'access_token' => $tokens['oauth_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
        ];
    }

    /**
     * @return list<array{value: string, label: string, domain: string}>
     */
    public function listCounters(string $token): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'OAuth '.$token,
        ])->get('https://api-metrika.yandex.net/management/v1/counters');

        if ($response->failed()) {
            throw new \RuntimeException('Не удалось получить список счётчиков Яндекс Метрики');
        }

        $counters = $response->json('counters') ?? [];
        $options = [];

        foreach ($counters as $counter) {
            if (! is_array($counter)) {
                continue;
            }

            $id = (int) ($counter['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $domain = (string) (
                $counter['site2']['site']
                ?? $counter['site']
                ?? $counter['name']
                ?? ''
            );

            $options[] = [
                'value' => (string) $id,
                'label' => $domain !== '' ? $id.' ('.$domain.')' : (string) $id,
                'domain' => $domain,
            ];
        }

        return $options;
    }

    /**
     * @return array<string, string|null>|null
     */
    public function fetchOauthUserProfile(string $token): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'OAuth '.$token,
            ])->get('https://login.yandex.ru/info', [
                'format' => 'json',
                'with_openid_identity' => 1,
            ]);
        } catch (\Throwable) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $login = filled($data['login'] ?? null) ? (string) $data['login'] : null;
        $displayName = filled($data['display_name'] ?? null)
            ? (string) $data['display_name']
            : (filled($data['real_name'] ?? null) ? (string) $data['real_name'] : null);

        $avatarId = filled($data['default_avatar_id'] ?? null)
            ? (string) $data['default_avatar_id']
            : null;

        return [
            'oauth_yandex_user_id' => filled($data['id'] ?? null) ? (string) $data['id'] : null,
            'oauth_yandex_login' => $login,
            'oauth_yandex_display_name' => $displayName,
            'oauth_yandex_avatar_url' => $avatarId !== null
                ? YandexDirectService::buildYandexAvatarUrl($avatarId)
                : null,
        ];
    }
}
