<?php

use App\Livewire\Concerns\VerifiesYandexSmartCaptcha;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')] class extends Component {
    use VerifiesYandexSmartCaptcha;

    public int $step = 1;

    #[Validate('required', onUpdate: false)]
    #[Validate('email', message: 'некорректный email', onUpdate: false)]
    public string $email = '';

    #[Computed]
    public function isStep1Ready(): bool
    {
        return filled(trim($this->email));
    }

    public function validateField(string $field): void
    {
        $this->validateOnly($field);
    }

    public function nextStep(): void
    {
        $this->verifySmartCaptcha('forgot-password-step1-captcha');
        $this->validateOnly('email');

        $user = User::where('email', $this->email)->first();

        if ($user) {
            $token = Password::broker()->createToken($user);

            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);

            Mail::to($user->email)->send(new ResetPasswordMail(
                email: $user->email,
                resetUrl: $resetUrl,
            ));
        }

        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }
};
