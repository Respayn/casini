<?php

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Concerns\VerifiesYandexSmartCaptcha;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')]
class extends Component {
    use RedirectsAfterAuth;
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

        $login = trim($this->userLogin);

        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->where('login', $login)
                    ->orWhere('email', $login);
            })
            ->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'userLogin' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'userLogin' => 'Подтвердите email по ссылке из письма',
            ]);
        }

        Auth::login($user);
        Session::regenerate();
        $this->bindCurrentAgency($user);

        $this->redirectIntended(default: $this->homeRouteAfterAuth(), navigate: true);
    }
};
