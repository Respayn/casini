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
                this.widget = window.smartCaptcha.render('{{ $captchaId }}', {
                    sitekey: '{{ $clientKey }}',
                    invisible: true,
                    callback: (token) => {
                        console.log('captcha callback', token);
                        this.token = token;
                        this.dispatchSuccess();
                    },
                });
                console.log('init', this.widget);
            },
            onExecuteCaptcha(event) {
                console.log('onExecuteCaptcha', event.detail.captchaId);
                if (event.detail.captchaId !== '{{ $captchaId }}') return;
                window.smartCaptcha.execute(this.widget);
            },
            onResetCaptcha(event) {
                console.log('onResetCaptcha', event.detail.captchaId);
                if (event.detail.captchaId !== '{{ $captchaId }}') return;
                window.smartCaptcha.destroy(this.widget);
                this.init();
            },
            dispatchSuccess() {
                console.log('dispatchSuccess');
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
        <div id="{{ $captchaId }}"></div>
        <input
            name="captcha_token"
            type="hidden"
            x-model="token"
        >
    </div>
@endif
