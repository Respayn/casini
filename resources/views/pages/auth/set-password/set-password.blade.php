<div>
    @if($step === 1)
        <div wire:key="invitation-step-1">
            <x-auth.back-link :href="route('login')" />
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Задайте пароль</h1>
                </div>

                <x-auth.captcha-form captcha-id="invitation-captcha" wire-method="savePassword">
                    <div class="flex flex-col gap-4">
                        <p class="text-sm text-primary-text">
                            Придумайте пароль, чтобы активировать доступ в Касини.
                        </p>

                        <div class="flex flex-col gap-2">
                            <x-form.form-label
                                class="text-caption-text font-normal"
                                required
                                tooltip="пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры"
                            >
                                Пароль
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
                            class="mt-4 h-14 w-full text-lg font-medium"
                            type="submit"
                            label="Активировать доступ"
                            variant="primary"
                            :disabled="!$this->isStep1Ready"
                        />
                    </div>
                </x-auth.captcha-form>
            </x-auth.card>
        </div>
    @elseif($step === 2)
        <div wire:key="invitation-step-2">
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Доступ активирован</h1>
                </div>
                <div>
                    Пароль сохранён, можно входить в Касини.
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
    @elseif($step === 3)
        <div wire:key="invitation-step-3">
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Ссылка уже использована</h1>
                </div>
                <p>Пароль для этого аккаунта уже задан. Войдите с ним на странице входа.</p>
            </x-auth.card>
        </div>
    @else
        <div wire:key="invitation-step-0">
            <x-auth.card>
                <div class="mb-7 flex items-center">
                    <h1 class="text-[28px] font-semibold">Ссылка недействительна</h1>
                </div>
                <p>Ссылка недействительна или устарела. Попросите отправить инструкции ещё раз.</p>
            </x-auth.card>
        </div>
    @endif
</div>
