<?php

namespace App\Livewire\Landing;

use App\Mail\SubscribeToNews;
use App\Serivces\YandexSmartCaptchaService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class SubscribeToNewsForm extends Component
{
    public string $email = '';
    public string $captchaToken = '';

    private YandexSmartCaptchaService $yandexSmartCaptchaService;

    public function boot(YandexSmartCaptchaService $yandexSmartCaptchaService)
    {
        $this->yandexSmartCaptchaService = $yandexSmartCaptchaService;
    }

    public function submit()
    {
        if (config('services.yandex.smartcaptcha.enabled')) {
            $verifyResult = $this->yandexSmartCaptchaService->verify(
                token: $this->captchaToken,
                clientIp: request()->ip()
            );

            if ($verifyResult->isFailure()) {
                $this->js('alert("' . $verifyResult->getError() . '")');
                return;
            }

            $this->dispatch('reset-captcha', captchaId: 'subscribe-to-news-captcha');
        }

        Mail::to('syrtsev@softorium.pro')
            ->send(new SubscribeToNews($this->email));

        $this->dispatch('form-submitted', id: 'subscribe-to-news');
        $this->js('alert("Спасибо! Новости сервиса теперь будут приходить к вам на почту")');
    }

    public function render()
    {
        return view('livewire.landing.subscribe-to-news-form');
    }
}
