<?php

use App\Livewire\Concerns\VerifiesYandexSmartCaptcha;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')] class extends Component {
    use VerifiesYandexSmartCaptcha;

    public int $step = 1;

    public string $token = '';

    public string $email = '';

    #[Validate('required', message: 'Поле пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('min:6', message: 'слишком короткий пароль', onUpdate: false)]
    #[Validate('regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', onUpdate: false)]
    public string $password = '';

    #[Validate('required', message: 'Поле повторите пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('same:password', message: 'пароли не совпадают', onUpdate: false)]
    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    #[Computed]
    public function isStep1Ready(): bool
    {
        return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $this->password)
            && $this->password === $this->passwordConfirmation;
    }

    public function validateField(string $field): void
    {
        $this->validateOnly($field);
    }

    public function resetPassword(): void
    {
        $this->verifySmartCaptcha('forgot-password-captcha');
        $this->validateOnly('password');
        $this->validateOnly('passwordConfirmation');

        if ($this->email === '') {
            throw ValidationException::withMessages([
                'email' => 'email не найден',
            ]);
        }

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->passwordConfirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Ссылка для восстановления пароля недействительна или устарела.',
            ]);
        }

        $this->step = 2;
    }

    public function enterAccount(): void
    {
        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        Auth::login($user);
        Session::regenerate();

        $this->redirectIntended(
            default: route('system-settings.dictionaries', absolute: false),
            navigate: true
        );
    }
};
