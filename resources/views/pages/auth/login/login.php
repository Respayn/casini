<?php

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Concerns\VerifiesYandexSmartCaptcha;
use App\Services\Auth\LoginService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')]
class extends Component
{
    use RedirectsAfterAuth;
    use VerifiesYandexSmartCaptcha;

    #[Validate('required', message: 'Поле логин или email обязательно для заполнения', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    public string $userLogin = '';

    #[Validate('required', message: 'Поле пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    public string $password = '';

    public ?string $resendStatus = null;

    #[Computed]
    public function isLoginReady(): bool
    {
        return filled(trim($this->userLogin)) && filled(trim($this->password));
    }

    public function validateField(string $field): void
    {
        $this->validateOnly($field);
    }

    public function login(LoginService $loginService): void
    {
        $this->resendStatus = null;
        $this->verifySmartCaptcha('login-captcha');
        $this->validate();

        $user = $loginService->attempt(trim($this->userLogin), $this->password);

        Session::regenerate();
        $this->bindCurrentAgency($user);

        $intended = Session::get('url.intended');
        $landingPath = parse_url(route('landing'), PHP_URL_PATH) ?: '/';
        $intendedPath = is_string($intended) ? (parse_url($intended, PHP_URL_PATH) ?: $intended) : null;

        if ($intendedPath === $landingPath || $intendedPath === '/') {
            Session::forget('url.intended');
        }

        $this->redirectIntended(default: $this->homeRouteAfterAuth(), navigate: true);
    }

    public function resendVerificationEmail(LoginService $loginService): void
    {
        $this->resendStatus = null;
        $this->validate([
            'userLogin' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($this->userLogin);
        $throttleKey = 'resend-verification:'.mb_strtolower($login).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $this->addError('pending_email', __('auth.verification_resend_throttle'));

            return;
        }

        RateLimiter::hit($throttleKey, 600);

        $sent = $loginService->resendVerificationEmail($login, $this->password);

        if (! $sent) {
            // Не раскрываем причину (нет пользователя / неверный пароль / другой статус)
            $this->addError('pending_email', __('auth.pending_email'));

            return;
        }

        $this->resendStatus = __('auth.verification_resent');
        $this->resetErrorBag('pending_email');
    }
};
