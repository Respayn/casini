@props(['captchaId'])
@if (config('services.yandex.smartcaptcha.enabled'))
    @php
        $clientKey = config('services.yandex.smartcaptcha.client_key');
    @endphp
    <div
        x-data="{
            widget: null,
            token: null,
            init() {
                const container = document.getElementById('{{ $captchaId }}');
                if (!window.smartCaptcha || !container) {
                    return;
                }

                this.widget = window.smartCaptcha.render(container, {
                    sitekey: '{{ $clientKey }}',
                    invisible: true,
                    callback: (token) => {
                        this.token = token;
                        this.dispatchSuccess();
                    },
                });
            },
            onExecuteCaptcha(event) {
                if (event.detail.captchaId !== '{{ $captchaId }}') return;
                window.smartCaptcha.execute(this.widget);
            },
            onResetCaptcha(event) {
                if (event.detail.captchaId !== '{{ $captchaId }}') return;
                window.smartCaptcha.destroy(this.widget);
                this.init();
            },
            dispatchSuccess() {
                this.$dispatch('captcha-success', {
                    captchaId: '{{ $captchaId }}',
                    token: this.token
                });
            }
        }"
        x-on:execute-captcha.window="onExecuteCaptcha"
        x-on:reset-captcha.window="onResetCaptcha"
        x-modelable="token"
        {{ $attributes }}
    >
        {{-- Protect Yandex DOM from Livewire morph (wire:model.live on form fields). --}}
        <div wire:ignore>
            <div id="{{ $captchaId }}"></div>
        </div>
        <input
            name="captcha_token"
            type="hidden"
            x-model="token"
        >
    </div>
@endif
