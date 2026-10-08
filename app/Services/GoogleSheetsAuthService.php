<?php

namespace App\Services;

use App\Clients\GoogleSheets\GoogleSheetsOAuthClient;

class GoogleSheetsAuthService
{
    public function __construct(
        private GoogleSheetsOAuthClient $oauthClient
    ) {}

    public function exchangeCode(string $code): array
    {
        return $this->oauthClient->getAccessToken(
            (string) config('services.google_sheets.client_id'),
            (string) config('services.google_sheets.client_secret'),
            $code,
            (string) config('services.google_sheets.redirect_uri')
        );
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->oauthClient->refreshToken(
            (string) config('services.google_sheets.client_id'),
            (string) config('services.google_sheets.client_secret'),
            $refreshToken
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchUserProfile(string $accessToken): ?array
    {
        return $this->oauthClient->fetchUserProfile($accessToken);
    }
}
