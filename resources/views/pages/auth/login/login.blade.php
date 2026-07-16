<div>
    <x-auth.card>
        <div class="mb-7 flex items-center justify-between">
            <h1 class="text-[28px] leading-tight font-semibold text-gray-900">
                Войти в аккаунт
            </h1>
            <a href="{{ route('register') }}"
               class="font-medium text-[18px] text-caption-text hover:underline">
                Регистрация
            </a>
        </div>

        <x-auth.captcha-form captcha-id="login-captcha" wire-method="login">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <x-form.form-label class="text-caption-text font-normal" required>
                        Логин или Email
                    </x-form.form-label>
                    <x-form.input-text
                        wire:model.live="userLogin"
                        wire:blur="validateField('userLogin')"
                        icon="icons.mail"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <x-form.form-label class="text-caption-text font-normal" required>
                        Пароль
                    </x-form.form-label>
                    <x-form.input-text
                        wire:model.live="password"
                        wire:blur="validateField('password')"
                        icon="icons.lock"
                        type="password"
                    />
                </div>

                <div>
                    <a href="{{ route('password.request') }}"
                       class="text-gray-500 hover:underline">
                        Забыли пароль?
                    </a>
                </div>

                @error('captchaToken')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror

                <x-button.button
                    class="mt-4 h-14 w-full text-lg font-medium"
                    type="submit"
                    label="Войти"
                    variant="primary"
                    :disabled="!$this->isLoginReady"
                />
            </div>
        </x-auth.captcha-form>
    </x-auth.card>
</div>
