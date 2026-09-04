<div>
    <x-auth.card>
        <div class="mb-7 flex items-center justify-between">
            <h1 class="text-[28px] leading-tight font-semibold text-gray-900">
                Войти в аккаунт
            </h1>
            @if (config('app.registration_enabled'))
                <a href="{{ route('register') }}"
                   class="font-medium text-[18px] text-caption-text hover:underline">
                    Регистрация
                </a>
            @endif
        </div>

        @if (session('status') || $resendStatus)
            <div class="border-primary text-primary-text mb-4 break-words rounded-lg border bg-blue-50 p-4 text-sm">
                {{ $resendStatus ?: session('status') }}
            </div>
        @endif

        @php
            $pendingNotice = $errors->first('pending_email') ?: session('pending_email');
            $inactiveNotice = $errors->first('inactive') ?: session('inactive');
        @endphp

        @if ($pendingNotice && ! $resendStatus)
            <div
                class="border-warning-red text-warning-red mb-4 break-words rounded-lg border bg-red-50 p-4 text-sm"
                role="alert"
            >
                <p>{{ $pendingNotice }}</p>
                <button
                    type="button"
                    wire:click="resendVerificationEmail"
                    wire:loading.attr="disabled"
                    class="text-primary mt-2 font-medium underline hover:no-underline disabled:opacity-60"
                >
                    Повторно выслать письмо
                </button>
            </div>
        @elseif ($inactiveNotice)
            <div
                class="border-warning-red text-warning-red mb-4 break-words rounded-lg border bg-red-50 p-4 text-sm"
                role="alert"
            >
                {{ $inactiveNotice }}
            </div>
        @endif

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
                        wire:model="password"
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
