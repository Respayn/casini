<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string')]
    public string $userLogin = '';

    #[Validate('required|string')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        if (!Auth::attempt(['login' => $this->userLogin, 'password' => $this->password])) {
            throw ValidationException::withMessages([
                'userLogin' => __('auth.failed'),
            ]);
        }

        Session::regenerate();

        $this->redirectIntended(default: route('system-settings.dictionaries', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-7">
        <h1 class="text-[28px] font-semibold">Войти в аккаунт</h1>
    </div>

    <x-form.form wire:submit="login">
        <div>
            <x-form.input-text
                class="mb-4"
                label="Логин"
                wire:model="userLogin"
                required
            />
            @error('userLogin')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <x-form.input-text
                label="Пароль"
                wire:model="password"
                required
            />
            @error('password')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
        </div>

        <x-button.button
            class="mt-6 w-full"
            type="submit"
            label="Войти"
            variant="primary"
        />
    </x-form.form>
</div>
