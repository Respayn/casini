<div>
    @if($step === 1)
        <div wire:key="reset-step-1">
            <x-auth.back-link :href="route('login')" />
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Восстановление пароля</h1>
                </div>

                <x-auth.captcha-form captcha-id="forgot-password-captcha" wire-method="resetPassword">
                    <div class="flex flex-col gap-4">
                        @error('email')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-col gap-2">
                            <x-form.form-label
                                class="text-caption-text font-normal"
                                required
                                tooltip="пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры"
                            >
                                Новый пароль
                            </x-form.form-label>
                            <x-form.input-text
                                wire:model.live="password"
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
                                wire:model.live="passwordConfirmation"
                                wire:blur="validateField('passwordConfirmation')"
                                icon="icons.lock"
                                type="password"
                            />
                        </div>

                        @error('captchaToken')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        <x-button.button
                            class="mt-4 h-14 w-full text-lg font-medium"
                            type="submit"
                            label="Сменить пароль"
                            variant="primary"
                            :disabled="!$this->isStep1Ready"
                        />
                    </div>
                </x-auth.captcha-form>
            </x-auth.card>
        </div>

    @elseif($step === 2)
        <div wire:key="reset-step-2">
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Восстановление пароля</h1>
                </div>
                <div>
                    Пароль успешно изменён.
                    <button
                        type="button"
                        wire:click="enterAccount"
                        class="font-semibold text-caption-text hover:underline"
                    >
                        Войти в аккаунт
                    </button>
                </div>
            </x-auth.card>
        </div>
    @endif
</div>
