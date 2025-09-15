<?php

namespace App\Livewire\Landing;

use App\Mail\EarlyAccessRequest;
use App\Serivces\YandexSmartCaptchaService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class EarlyAccessRequestForm extends Component
{
    public string $team = '';
    public string $telegram = '';
    public string $agency = '';
    public string $sourceBlock = '';
    public string $captchaToken = '';

    private YandexSmartCaptchaService $yandexSmartCaptchaService;

    public function boot(YandexSmartCaptchaService $yandexSmartCaptchaService)
    {
        $this->yandexSmartCaptchaService = $yandexSmartCaptchaService;
    }

    public function sendEarlyAccessRequestForm(): void
    {
        if (config('services.yandex.smartcaptcha.enabled')) {
            $verifyResult = $this->yandexSmartCaptchaService->verify(
                $this->captchaToken,
                request()->ip()
            );

            if ($verifyResult->isFailure()) {
                $this->js('alert("' . $verifyResult->getError() . '")');
                return;
            }

            $this->dispatch('reset-captcha', captchaId: 'early-access-captcha');
        }

        Mail::to('info@casini.ru')
            ->send(new EarlyAccessRequest(
                $this->team,
                $this->telegram,
                $this->agency,
                $this->sourceBlock
            ));

        $this->dispatch('form-submitted', id: 'early-access');
        $this->js('alert("Заявка отправлена! Мы свяжемся с вами.")');
    }

    public function render()
    {
        return view('livewire.landing.early-access-request-form');
    }
}
