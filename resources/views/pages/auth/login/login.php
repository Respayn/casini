<?php

use App\Livewire\Concerns\VerifiesYandexSmartCaptcha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')]
class extends Component {
    use VerifiesYandexSmartCaptcha;

    #[Validate('required', message: 'Поле логин или email обязательно для заполнения', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    public string $userLogin = '';

    #[Validate('required', message: 'Поле пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    public string $password = '';

    #[Computed]
    public function isLoginReady(): bool
    {
        return filled(trim($this->userLogin)) && filled(trim($this->password));
    }

    public function validateField(string $field): void
    {
        $this->validateOnly($field);
    }

    public function login(): void
    {
        $this->verifySmartCaptcha('login-captcha');
        $this->validate();

        if (! Auth::attempt(['login' => $this->userLogin, 'password' => $this->password])) {
            throw ValidationException::withMessages([
                'userLogin' => __('auth.failed'),
            ]);
        }

        Session::regenerate();

        $this->redirectIntended(default: route('system-settings.dictionaries', absolute: false), navigate: true);
    }
};
