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
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-[28px] leading-tight font-semibold text-gray-900">
            Войти в аккаунт
        </h1>
        <a href="{{ route('register') }}"
           class="font-medium text-[18px] text-[#486388] hover:underline">
            Регистрация
        </a>
    </div>

    <x-form.form wire:submit="login">
        <div class="flex flex-col gap-8">
            <div>
                <x-form.input-text
                    class="h-12 text-base"
                    label="Логин или Email *"
                    label-class="text-sm font-medium text-gray-700"
                    wire:model="userLogin"
                    icon="icons.mail"
                    required
                />
            </div>

            <div>
                <x-form.input-text
                    class="h-12 text-base"
                    label="Пароль *"
                    label-class="text-sm font-medium text-gray-700"
                    wire:model="password"
                    icon="icons.lock"
                    type="password"
                    required
                />
            </div>

            <div>
                <a href="{{ route('password.request') }}"
                   class="text-gray-500 hover:underline">
                    Забыли пароль?
                </a>
            </div>

            <x-button.button
                class="w-full h-14 text-lg font-medium"
                type="submit"
                label="Войти"
                variant="primary"
            />
        </div>
    </x-form.form>
</div>
