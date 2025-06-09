<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use App\Models\User;

new #[Layout('components.layouts.auth')] class extends Component {
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
?>

<div>
    {{-- Шаг 1: Ввод Email --}}
    @if($step === 1)
        <div wire:key="forgot-step-1">
            <div class="mb-7 flex items-center">
                <a href="{{ route('login') }}">
                    <button
                        type="button"
                        class="inline-flex items-center gap-3 max-h-[26px] text-[18px] text-secondary-text hover:text-[#486388] font-normal"
                    >
                        <x-icons.arrow-left class="w-5 h-5" />
                        <span>Назад</span>
                    </button>
                </a>
                <h1 class="text-[28px] font-semibold ml-8">Восстановление пароля</h1>
            </div>

            <x-form.form wire:submit.prevent="nextStep">
                <div class="flex flex-col gap-8">
                    <div>
                        <x-form.input-text
                            class="h-12 text-base"
                            label="Email *"
                            label-class="text-sm font-medium text-gray-700"
                            wire:model="email"
                            icon="icons.mail"
                            required
                        />
                    </div>

                    <x-button.button
                        class="w-full h-14 text-lg font-medium mt-2"
                        type="submit"
                        label="Восстановить"
                        variant="primary"
                    />
                </div>
            </x-form.form>

            <div class="mt-6 text-center text-gray-500">
                На указанный email вышлем инструкции по восстановлению пароля
            </div>
        </div>

        {{-- Шаг 2: Новый пароль --}}
    @elseif($step === 2)
        <div wire:key="forgot-step-2">
            {{-- Кнопка "Назад" — строго по макету --}}
            <div class="mb-4">
                <button
                    type="button"
                    wire:click="prevStep"
                    class="inline-flex items-center gap-3 max-h-[26px] text-[18px] text-secondary-text hover:text-[#486388] font-normal"
                >
                    <x-icons.arrow-left class="w-5 h-5" />
                    <span>Назад</span>
                </button>
            </div>
            <div class="mb-7 flex items-center">
                <h1 class="text-[28px] font-semibold">Восстановление пароля</h1>
            </div>

            <x-form.form wire:submit.prevent="resetPassword">
                <div class="flex flex-col gap-8">
                    <div>
                        <x-form.input-text
                            class="h-12 text-base"
                            label="Новый пароль *"
                            label-class="text-sm font-medium text-gray-700"
                            wire:model="password"
                            icon="icons.lock"
                            type="password"
                            required
                        />
                        @error('password')
                        <div class="mt-1 text-sm text-red-500">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-form.input-text
                            class="h-12 text-base"
                            label="Повторите пароль *"
                            label-class="text-sm font-medium text-gray-700"
                            wire:model="passwordConfirmation"
                            icon="icons.lock"
                            type="password"
                            required
                        />
                        @error('passwordConfirmation')
                        <div class="mt-1 text-sm text-red-500">{{ $message }}</div>
                        @enderror
                    </div>

                    <x-button.button
                        class="w-full h-14 text-lg font-medium mt-2"
                        type="submit"
                        label="Сохранить пароль и войти"
                        variant="primary"
                    />
                </div>
            </x-form.form>
        </div>

        {{-- Шаг 3: Успех --}}
    @elseif($step === 3)
        <div wire:key="forgot-step-3">
            {{-- Кнопка "Назад" — строго по макету --}}
            <div class="mb-4">
                <button
                    type="button"
                    wire:click="prevStep"
                    class="inline-flex items-center gap-3 max-h-[26px] text-[18px] text-secondary-text hover:text-[#486388] font-normal"
                >
                    <x-icons.arrow-left class="w-5 h-5" />
                    <span>Назад</span>
                </button>
            </div>
            <div class="mb-7 flex items-center">
                <h1 class="text-[28px] font-semibold">Восстановление пароля</h1>
            </div>
            <div>
                Пароль успешно изменён.
                <a href="{{ route('login') }}"
                   class="text-[#486388] font-semibold hover:underline">
                    Войти в аккаунт
                </a>
            </div>
        </div>
    @endif
</div>
