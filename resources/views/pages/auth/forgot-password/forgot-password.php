<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Models\User;

new #[Layout('layouts::auth')] class extends Component {
    public int $step = 1;

    // Шаг 1: email
    #[Validate('required|email')]
    public string $email = '';

    // Шаг 2: новый пароль
    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|string|same:password')]
    public string $passwordConfirmation = '';

    public function nextStep(): void
    {
        $this->validateOnly('email');
        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function resetPassword(): void
    {
        $this->validateOnly('password');
        $this->validateOnly('passwordConfirmation');

        $user = User::where('email', $this->email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Пользователь с таким E-mail не найден.',
            ]);
        }

        $user->password = Hash::make($this->password);
        $user->save();

        $this->step = 3;
    }
};
