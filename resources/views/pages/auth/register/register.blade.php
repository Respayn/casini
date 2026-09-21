<div>
    @if($step === 1)
        <div wire:key="register-step-1">
            <x-auth.card>
                <div class="mb-7 flex items-center justify-between">
                    <h1 class="text-[28px] font-semibold">Регистрация</h1>
                    <a href="{{ route('login') }}"
                       class="font-medium text-[18px] text-caption-text hover:underline">
                        Войти в аккаунт
                    </a>
                </div>
                <x-form.form wire:submit.prevent="nextStep">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <x-form.form-label class="text-caption-text font-normal" required>
                                Имя
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model.live="firstName"
                                wire:blur="validateField('firstName')"
                                icon="icons.user"
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <x-form.form-label class="text-caption-text font-normal" required>
                                Фамилия
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model.live="lastName"
                                wire:blur="validateField('lastName')"
                                icon="icons.user"
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <x-form.form-label
                                class="text-caption-text font-normal"
                                required
                                tooltip="Название агентства позже можно будет изменить"
                            >
                                Название агентства
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model="agencyName"
                                icon="icons.edit-form"
                                disabled
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <x-form.form-label
                                class="text-caption-text font-normal"
                                required
                                tooltip="от выбранного часового пояса зависит время автоматического снятия показателей в отчетах"
                            >
                                Основной часовой пояс агентства
                            </x-form.form-label>
                            <x-form.select
                                wire:model="timezone"
                                :options="\App\Dictionaries\TimeZoneDictionary::optionsForSelect()"
                                required
                                disabled
                            />
                        </div>
                        <x-button.button
                            class="mt-4 h-14 text-lg font-medium"
                            type="submit"
                            label="Далее"
                            variant="primary"
                            :disabled="!$this->isStep1Ready"
                        />
                    </div>
                </x-form.form>
            </x-auth.card>
        </div>

    @elseif($step === 2)
        <div wire:key="register-step-2">
            <x-auth.back-link wire:click="prevStep" />
            <x-auth.card>
                <div class="mb-7 flex items-center justify-between">
                    <h1 class="text-[28px] font-semibold">Регистрация</h1>
                    <a href="{{ route('login') }}"
                       class="font-medium text-[18px] text-caption-text hover:underline">
                        Войти в аккаунт
                    </a>
                </div>
                <x-auth.captcha-form captcha-id="register-captcha" wire-method="register">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <x-form.form-label class="text-caption-text font-normal" required>
                                Email
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model.live="email"
                                wire:blur="validateField('email')"
                                icon="icons.mail"
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <x-form.form-label class="text-caption-text font-normal" required>
                                Телефон
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model.live="phone"
                                wire:blur="validateField('phone')"
                                icon="icons.phone"
                                mask="phone"
                                placeholder="+7 (___) ___-__-__"
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <x-form.form-label
                                class="text-caption-text font-normal"
                                required
                                tooltip="пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры"
                            >
                                Придумайте пароль
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model="password"
                                wire:blur="validateField('password')"
                                icon="icons.lock"
                                type="password"
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <x-form.form-label class="text-caption-text font-normal" required>
                                Повторите пароль
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model="passwordConfirmation"
                                wire:blur="validateField('passwordConfirmation')"
                                icon="icons.lock"
                                type="password"
                            />
                        </div>

                        @error('captchaToken')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        <x-button.button
                            class="mt-4 h-14 text-lg font-medium"
                            type="submit"
                            label="Завершить регистрацию"
                            variant="primary"
                            :disabled="!$this->isStep2Ready"
                        />
                    </div>
                </x-auth.captcha-form>

                <div class="mt-4 text-center text-xs text-gray-400">
                    Нажимая на кнопку вы соглашаетесь с
                    <a href="{{ route('privacy') }}" class="text-blue-500 hover:underline" wire:navigate>
                        политикой обработки персональных данных
                    </a>
                </div>
            </x-auth.card>
        </div>

    @elseif($step === 3)
        <div
            wire:key="register-step-3"
            x-data
            x-init="
                history.pushState({ casiniRegisterComplete: true }, '', window.location.href);
                const onPopState = () => {
                    window.removeEventListener('popstate', onPopState);
                    window.location.href = @js(route('login'));
                };
                window.addEventListener('popstate', onPopState);
            "
        >
            <x-auth.card>
                <div class="mb-7 flex items-center justify-between">
                    <h1 class="text-[28px] font-semibold">Регистрация</h1>
                    <a href="{{ route('login') }}"
                       class="font-medium text-[18px] text-caption-text hover:underline">
                        Войти в аккаунт
                    </a>
                </div>
                <div>
                    Для завершения регистрации —
                    <span class="font-semibold text-caption-text">
                        проверьте ваш почтовый ящик
                    </span>
                </div>
            </x-auth.card>
        </div>
    @endif
</div>
