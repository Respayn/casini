<?php

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Concerns\VerifiesYandexSmartCaptcha;
use App\Models\User;
use App\Services\Auth\UserInvitationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')] class extends Component
{
    use RedirectsAfterAuth;
    use VerifiesYandexSmartCaptcha;

    public int $step = 1;

    public int $userId = 0;

    public string $expires = '';

    public string $signature = '';

    #[Validate('required', message: 'Поле пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('min:6', message: 'слишком короткий пароль', onUpdate: false)]
    #[Validate('regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', message: 'пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры', onUpdate: false)]
    public string $password = '';

    #[Validate('required', message: 'Поле повторите пароль обязательно для заполнения', onUpdate: false)]
    #[Validate('same:password', message: 'пароли не совпадают', onUpdate: false)]
    public string $passwordConfirmation = '';

    public function mount(User $user): void
    {
        Session::forget('url.intended');

        $this->userId = $user->id;
        $this->expires = (string) request()->query('expires', '');
        $this->signature = (string) request()->query('signature', '');
        $this->step = $this->resolveStep();
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

    public function savePassword(UserInvitationService $invitations): void
    {
        $this->verifySmartCaptcha('invitation-captcha');

        $user = $this->pendingUser($invitations);
        if (! $user instanceof User) {
            return;
        }

        $this->validateOnly('password');
        $this->validateOnly('passwordConfirmation');

        $invitations->accept($user, $this->password);
        $this->step = 2;
    }

    public function enterAccount(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        Auth::login($user);
        Session::regenerate();
        $this->bindCurrentAgency($user);
        Session::forget('url.intended');

        $this->redirect($this->homeRouteAfterAuth(), navigate: true);
    }

    private function resolveStep(): int
    {
        $user = User::query()->find($this->userId);
        $invitations = app(UserInvitationService::class);

        if (! $user instanceof User || ! $invitations->hasValidSignature($user, $this->expires, $this->signature)) {
            return 0;
        }

        if (! $invitations->canAccept($user)) {
            return 3;
        }

        return 1;
    }

    private function pendingUser(UserInvitationService $invitations): ?User
    {
        $user = User::query()->find($this->userId);

        if (! $user instanceof User || ! $invitations->hasValidSignature($user, $this->expires, $this->signature)) {
            $this->step = 0;

            throw ValidationException::withMessages([
                'password' => 'Ссылка недействительна или устарела.',
            ]);
        }

        if (! $invitations->canAccept($user)) {
            $this->step = 3;

            throw ValidationException::withMessages([
                'password' => 'Ссылка уже использована.',
            ]);
        }

        return $user;
    }
};
