<div>
    @if($step === 1)
        <div wire:key="forgot-step-1">
            <x-auth.back-link :href="route('login')" />
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Восстановление пароля</h1>
                </div>

                <x-auth.captcha-form captcha-id="forgot-password-step1-captcha" wire-method="nextStep">
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

                        @error('captchaToken')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        <x-button.button
                            class="mt-4 h-14 w-full text-lg font-medium"
                            type="submit"
                            label="Восстановить"
                            variant="primary"
                            :disabled="!$this->isStep1Ready"
                        />
                    </div>
                </x-auth.captcha-form>
            </x-auth.card>
        </div>

    @elseif($step === 2)
        <div wire:key="forgot-step-2">
            <x-auth.back-link wire:click="prevStep" />
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Восстановление пароля</h1>
                </div>
                <div>
                    На ваш почтовый ящик
                    <span class="font-semibold text-caption-text">{{ $email }}</span>
                    отправлены инструкции по восстановлению пароля
                </div>
            </x-auth.card>
        </div>
    @endif
</div>
