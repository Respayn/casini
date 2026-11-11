<?php

namespace App\Services\GoogleSheets;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Ключ служебной учётки Google: JWT → access token для Sheets API.
 */
class GoogleSheetsServiceAccountCredentials
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    private ?string $cachedAccessToken = null;

    private ?int $cachedExpiresAt = null;

    public function isConfigured(): bool
    {
        $path = $this->jsonPath();

        return $path !== null && is_readable($path);
    }

    public function clientEmail(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $email = trim((string) ($this->loadKey()['client_email'] ?? ''));

        return $email !== '' ? $email : null;
    }

    public function accessToken(): string
    {
        if ($this->cachedAccessToken !== null
            && $this->cachedExpiresAt !== null
            && time() < ($this->cachedExpiresAt - 60)
        ) {
            return $this->cachedAccessToken;
        }

        $key = $this->loadKey();
        $assertion = $this->buildJwtAssertion($key);

        $response = Http::asForm()->post(self::TOKEN_URI, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Failed to refresh Google service account token: '.$response->body()
            );
        }

        $accessToken = (string) ($response->json('access_token') ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Google service account token response has no access_token.');
        }

        $this->cachedAccessToken = $accessToken;
        $this->cachedExpiresAt = time() + (int) ($response->json('expires_in') ?? 3600);

        return $this->cachedAccessToken;
    }

    /**
     * @return array{client_email: string, private_key: string, token_uri?: string}
     */
    private function loadKey(): array
    {
        $path = $this->jsonPath();

        if ($path === null || ! is_readable($path)) {
            throw new RuntimeException('Google Sheets service account key is missing.');
        }

        $raw = file_get_contents($path);

        if ($raw === false || $raw === '') {
            throw new RuntimeException('Google Sheets service account key is empty.');
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Google Sheets service account key is invalid JSON.');
        }

        $email = trim((string) ($decoded['client_email'] ?? ''));
        $privateKey = (string) ($decoded['private_key'] ?? '');

        if ($email === '' || $privateKey === '') {
            throw new RuntimeException('Google Sheets service account key lacks client_email or private_key.');
        }

        return [
            'client_email' => $email,
            'private_key' => $privateKey,
            'token_uri' => (string) ($decoded['token_uri'] ?? self::TOKEN_URI),
        ];
    }

    /**
     * @param  array{client_email: string, private_key: string, token_uri?: string}  $key
     */
    private function buildJwtAssertion(array $key): string
    {
        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $key['client_email'],
            'scope' => self::SCOPE,
            'aud' => $key['token_uri'] ?? self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$payload;
        $privateKey = openssl_pkey_get_private($key['private_key']);

        if ($privateKey === false) {
            throw new RuntimeException('Google Sheets service account private_key is invalid.');
        }

        $signature = '';

        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign Google service account JWT.');
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    private function jsonPath(): ?string
    {
        $path = trim((string) config('services.google_sheets.service_account_json', ''));

        return $path !== '' ? $path : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
