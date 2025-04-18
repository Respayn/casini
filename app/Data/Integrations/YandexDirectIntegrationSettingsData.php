<?php

namespace App\Data\Integrations;

use App\Data\IntegrationSettings\IntegrationSettingsData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

// TODO: Шифрование токенов
class YandexDirectIntegrationSettingsData extends IntegrationSettingsData
{
    public ?string $clientLogin;
    public ?string $accountId;
    public ?string $encryptedOauthToken;
    public ?string $encryptedRefreshToken;
    public ?string $tokenExpiresAt;
    public array $goals = [];
    public string $attributionModel = 'LSC';

    public static function fromSettings(Collection $settings): self
    {
        $data = new self();
        $data->clientLogin = $settings->get('client_login');
        $data->accountId = $settings->get('account_id');
        $data->encryptedOauthToken = $settings->get('oauth_token');
        $data->encryptedRefreshToken = $settings->get('refresh_token');
        $data->tokenExpiresAt = $settings->get('token_expires_at');
        $data->goals = $settings->get('goals', []);
        $data->attributionModel = $settings->get('attribution_model', 'LSC');
        return $data;
    }

    public function getOauthToken(): ?string
    {
        return $this->encryptedOauthToken
            ? Crypt::decryptString($this->encryptedOauthToken)
            : null;
    }

    public function setOauthToken(string $token): void
    {
        $this->encryptedOauthToken = Crypt::encryptString($token);
    }

    public function getRefreshToken(): ?string
    {
        return $this->encryptedRefreshToken
            ? Crypt::decryptString($this->encryptedRefreshToken)
            : null;
    }

    public function setRefreshToken(string $token): void
    {
        $this->encryptedRefreshToken = Crypt::encryptString($token);
    }
}
