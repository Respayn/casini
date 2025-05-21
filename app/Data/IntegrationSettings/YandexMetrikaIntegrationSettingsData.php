<?php

namespace App\Data\IntegrationSettings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

class YandexMetrikaIntegrationSettingsData extends IntegrationSettingsData
{
    public ?int $counterId;
    public ?string $encryptedOauthToken;
    public ?string $encryptedRefreshToken;
    public ?string $tokenExpiresAt;
    public array $goals = [];
    public string $attributionModel = 'lastsign';

    public static function fromSettings(Collection $settings): self
    {
        $data = new self();
        $data->counterId = $settings->get('counter_id');
        $data->encryptedOauthToken = Crypt::encryptString($settings->get('oauth_token'));
        $data->encryptedRefreshToken = Crypt::encryptString($settings->get('refresh_token'));
        $data->tokenExpiresAt = $settings->get('token_expires_at');
        $data->goals = $settings->get('goals', []);
        $data->attributionModel = $settings->get('attribution_model', 'lastsign');
        return $data;
    }

    public function getDecryptedOauthToken(): string
    {
        return Crypt::decryptString($this->encryptedOauthToken);
    }

    public function getDecryptedRefreshToken(): string
    {
        return Crypt::decryptString($this->encryptedRefreshToken);
    }
}
