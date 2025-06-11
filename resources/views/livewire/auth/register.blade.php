<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public int $step = 1;

    // Step 1
    #[Validate('required|string|max:255')]
    public string $firstName = '';

    #[Validate('required|string|max:255')]
    public string $lastName = '';

    #[Validate('required|string|max:255')]
    public string $agencyName = '';

    #[Validate('required|string|max:255')]
    public string $timezone = 'Москва';

    // Step 2
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|min:10|max:20')]
    public string $phone = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password = '';

    #[Validate('required|string|same:password')]
    public string $passwordConfirmation = '';

    public function nextStep()
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

    public function prevStep()
    {
        $this->step--;
    }

    public function register()
    {
        $this->validate();

        // Например:
        // $user = User::create([
        //     'first_name'            => $this->firstName,
        //     'last_name'             => $this->lastName,
        //     'agency_name'           => $this->agencyName,
        //     'timezone'              => $this->timezone,
        //     'email'                 => $this->email,
        //     'phone'                 => $this->phone,
        //     'password'              => Hash::make($this->password),
        // ]);

        $this->step = 3;
    }
};
?>

<div>
    @if($step === 1)
        <div wire:key="register-step-1">
            <div class="mb-7 flex items-center justify-between">
                <h1 class="text-[28px] font-semibold">Регистрация</h1>
                <a href="{{ route('login') }}"
                   class="font-medium text-[18px] text-[#486388] hover:underline">
                    Войти в аккаунт
                </a>
            </div>
            <x-form.form wire:submit.prevent="nextStep">
                <div class="flex flex-col gap-4">
                    <div>
                        <x-form.input-text
                            label="Имя*"
                            wire:model="firstName"
                            icon="icons.user"
                            required
                        />
                    </div>
                    <div>
                        <x-form.input-text
                            label="Фамилия*"
                            wire:model="lastName"
                            icon="icons.user"
                            required
                        />
                    </div>
                    <div>
                        <x-form.input-text
                            label="Название агентства *"
                            wire:model="agencyName"
                            icon="icons.edit-form"
                            required
                        />
                    </div>
                    <div>
                        <x-form.select
                            label="Основной часовой пояс агентства *"
                            wire:model="timezone"
                            :options="[
                                ['label'=>'Москва','value'=>'Москва'],
                                ['label'=>'Новосибирск','value'=>'Новосибирск']
                            ]"
                            required
                        />
                    </div>
                    <x-button.button
                        class="mt-4 h-14 text-lg font-medium"
                        type="submit"
                        label="Далее"
                        variant="primary"
                    />
                </div>
            </x-form.form>
        </div>

    @elseif($step === 2)
        <div wire:key="register-step-2">
            <div class="mb-4">
                <x-button
                    label="Назад"
                    icon="icons.arrow-left"
                    size="none"
                    variant="link"
                    wire:click="prevStep"
                    class="text-secondary-text inline-flex gap-3 items-center max-h-[26px] text-[18px]"
                />
            </div>
            <div class="mb-7 flex items-center justify-between">
                <h1 class="text-[28px] font-semibold">Регистрация</h1>
                <a href="{{ route('login') }}"
                   class="font-medium text-[18px] text-[#486388] hover:underline">
                    Войти в аккаунт
                </a>
            </div>
            <x-form.form wire:submit.prevent="register">
                <div class="flex flex-col gap-4">
                    <div>
                        <x-form.input-text
                            label="Email *"
                            wire:model="email"
                            icon="icons.mail"
                            required
                        />
                    </div>
                    <div>
                        <x-form.input-text
                            label="Телефон *"
                            wire:model="phone"
                            icon="icons.phone"
                            required
                        />
                    </div>
                    <div>
                        <x-form.input-text
                            label="Придумайте пароль *"
                            wire:model="password"
                            icon="icons.lock"
                            type="password"
                            required
                        />
                    </div>
                    <div>
                        <x-form.input-text
                            label="Повторите пароль *"
                            wire:model="passwordConfirmation"
                            icon="icons.lock"
                            type="password"
                            required
                        />
                    </div>
                    <div>
                        <a href="{{ route('password.request') }}"
                           class="text-gray-400 hover:underline">Забыли пароль?</a>
                    </div>
                    <x-button.button
                        class="mt-4 h-14 text-lg font-medium"
                        type="submit"
                        label="Завершить регистрацию"
                        variant="primary"
                    />
                </div>
            </x-form.form>

            <div class="mt-4 text-xs text-gray-400 text-center">
                Нажимая на кнопку вы соглашаетесь с
                <a href="/policy" class="text-blue-500 hover:underline">
                    политикой обработки персональных данных
                </a>
            </div>
        </div>

    @elseif($step === 3)
        <div wire:key="register-step-3">
            <div class="mb-4">
                <x-button
                    label="Назад"
                    icon="icons.arrow-left"
                    size="none"
                    variant="link"
                    wire:click="prevStep"
                    class="text-secondary-text inline-flex gap-3 items-center max-h-[26px] text-[18px]"
                />
            </div>
            <div class="mb-7 flex items-center justify-between">
                <h1 class="text-[28px] font-semibold">Регистрация</h1>
                <a href="{{ route('login') }}"
                   class="font-medium text-[18px] text-[#486388] hover:underline">
                    Войти в аккаунт
                </a>
            </div>
            <div>
                Для завершения регистрации —
                <span class="font-semibold text-[#486388]">
                    проверьте ваш почтовый ящик
                </span>
            </div>
        </div>
    @endif
</div>
