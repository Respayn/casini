<?php

use App\Enums\Role;
use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Concerns\VerifiesYandexSmartCaptcha;
use App\Mail\VerifyRegistrationMail;
use App\Models\Agency;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Permission\Models\Role as RoleModel;

new #[Layout('layouts::auth')] class extends Component {
    use RedirectsAfterAuth;
    use VerifiesYandexSmartCaptcha;

    public int $step = 1;

    // Step 1 — one rule per Validate (pipe-combined rules break when stacked)
    #[Validate('required', message: 'Поле имя обязательно для заполнения', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    #[Validate('max:255', onUpdate: false)]
    public string $firstName = '';

    #[Validate('required', message: 'Поле фамилия обязательно для заполнения', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    #[Validate('max:255', onUpdate: false)]
    public string $lastName = '';

    #[Validate('required', message: 'Поле название агентства обязательно для заполнения', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    #[Validate('max:255', onUpdate: false)]
    public string $agencyName = 'СайтАктив';

    #[Validate('required', onUpdate: false)]
    #[Validate('string', onUpdate: false)]
    #[Validate('max:255', onUpdate: false)]
    public string $timezone = 'Asia/Yekaterinburg';

    // Step 2
    #[Validate('required', onUpdate: false)]
    #[Validate('email', message: 'некорректный email', onUpdate: false)]
    public string $email = '';

    #[Validate('required', message: 'Поле телефон обязательно для заполнения', onUpdate: false)]
    #[Validate('regex:/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', message: 'некорректный телефон', onUpdate: false)]
    public string $phone = '';

    #[Validate('required', message: 'Поле пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('min:6', message: 'слишком короткий пароль', onUpdate: false)]
    #[Validate('regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', onUpdate: false)]
    public string $password = '';

    #[Validate('required', message: 'Поле повторите пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('same:password', message: 'пароли не совпадают', onUpdate: false)]
    public string $passwordConfirmation = '';

    public function mount(): void
    {
        if (! config('app.registration_enabled')) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    #[Computed]
    public function isStep1Ready(): bool
    {
        return filled(trim($this->firstName))
            && filled(trim($this->lastName))
            && filled(trim($this->agencyName))
            && filled(trim($this->timezone));
    }

    #[Computed]
    public function isStep2Ready(): bool
    {
        return filled(trim($this->email))
            && (bool) preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $this->phone)
            && (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $this->password)
            && $this->password === $this->passwordConfirmation;
    }

    public function validateField(string $field): void
    {
        $this->validateOnly($field);
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            foreach (['firstName', 'lastName', 'agencyName', 'timezone'] as $field) {
                $this->validateOnly($field);
            }
        }

        if ($this->step === 2) {
            foreach (['email', 'phone', 'password', 'passwordConfirmation'] as $field) {
                $this->validateOnly($field);
            }
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step <= 1) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->step--;
    }

    public function register(): void
    {
        $this->verifySmartCaptcha('register-captcha');
        $this->validate();

        $this->validate([
            'email' => [
                Rule::unique('users', 'email'),
            ],
        ], [
            'email.unique' => 'Не удалось завершить регистрацию. Проверьте данные или войдите',
        ]);

        $agency = Agency::query()
            ->where('name', 'СайтАктив')
            ->firstOrFail();

        $role = RoleModel::query()
            ->where('name', Role::DEFAULT->value)
            ->firstOrFail();

        $login = $this->generateUniqueLogin($this->email);

        /** @var UserService $userService */
        $userService = app(UserService::class);

        $userData = $userService->create([
            'login' => $login,
            'first_name' => trim($this->firstName),
            'last_name' => trim($this->lastName),
            'email' => trim($this->email),
            'phone' => $this->phone,
            'password' => $this->password,
            'is_active' => false,
            'email_verified_at' => null,
            'agency_id' => $agency->id,
            'role_id' => $role->id,
            'enable_important_notifications' => false,
            'enable_notifications' => false,
        ]);

        $user = User::query()->findOrFail($userData->id);

        $verifyUrl = URL::temporarySignedRoute(
            'register.verify',
            now()->addDay(),
            ['user' => $user->id],
        );

        Mail::to($user->email)->send(new VerifyRegistrationMail(
            email: $user->email,
            verifyUrl: $verifyUrl,
        ));

        $this->step = 3;
    }

    private function generateUniqueLogin(string $email): string
    {
        $base = Str::lower((string) Str::before($email, '@'));
        $base = preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'user';

        $login = $base;
        $suffix = 1;

        while (User::query()->where('login', $login)->exists()) {
            $login = $base.$suffix;
            $suffix++;
        }

        return $login;
    }
};
