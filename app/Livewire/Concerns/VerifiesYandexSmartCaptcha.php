<?php

namespace App\Livewire\Concerns;

use App\Services\YandexSmartCaptchaService;
use Illuminate\Validation\ValidationException;

trait VerifiesYandexSmartCaptcha
{
    public string $captchaToken = '';

    private YandexSmartCaptchaService $yandexSmartCaptchaService;

    public function bootVerifiesYandexSmartCaptcha(YandexSmartCaptchaService $yandexSmartCaptchaService): void
    {
        $this->yandexSmartCaptchaService = $yandexSmartCaptchaService;
    }

    protected function verifySmartCaptcha(string $captchaId): void
    {
        if (! config('services.yandex.smartcaptcha.enabled')) {
            return;
        }

        $verifyResult = $this->yandexSmartCaptchaService->verify(
            token: $this->captchaToken,
            clientIp: request()->ip() ?? ''
        );

        $this->dispatch('reset-captcha', captchaId: $captchaId);

        if ($verifyResult->isFailure()) {
            throw ValidationException::withMessages([
                'captchaToken' => $verifyResult->getError() ?: 'Капча не пройдена. Попробуйте ещё раз.',
            ]);
        }
    }
}
