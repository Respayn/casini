<?php

namespace App\Services;

use App\Clients\YandexDirect\YandexDirectOAuthClient;

class YandexDirectAuthService
{
    public function __construct(
        private YandexDirectOAuthClient $oauthClient
    ) {}

    public function exchangeCode(string $code): array
    {
        return $this->oauthClient->getAccessToken(
            config('services.yandex_direct.client_id'),
            config('services.yandex_direct.client_secret'),
            $code,
            config('services.yandex_direct.redirect_uri')
        );
    }
}
