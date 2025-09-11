<?php

namespace App\Serivces;

use App\OperationResult;
use Illuminate\Support\Facades\Http;

class YandexSmartCaptchaService
{
    public function verify(string $token, string $clientIp): OperationResult
    {
        $response = Http::asForm()->post('https://smartcaptcha.yandexcloud.net/validate', [
            'secret' => config('services.yandex.smartcaptcha.server_key'),
            'token' => $token,
            'ip' => $clientIp,
        ]);

        if (!($response->json('status') === 'ok')) {
            return OperationResult::failure('Капча не пройдена. Попробуйте ещё раз.');
        }

        return OperationResult::success();
    }
}
