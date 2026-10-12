<?php

namespace App\Clients\GoogleSheets;

use Illuminate\Support\Facades\Http;

class GoogleSheetsOAuthClient
{
    public function getAccessToken(
        string $clientId,
        string $clientSecret,
        string $code,
        string $redirectUri
    ): array {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to get Google access token: '.$response->body());
        }

        return $response->json();
    }

    public function refreshToken(
        string $clientId,
        string $clientSecret,
        string $refreshToken
    ): array {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to refresh Google token: '.$response->body());
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchUserProfile(string $accessToken): ?array
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }
}
