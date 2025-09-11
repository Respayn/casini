<form
    id="subscribeForm"
    x-data="{
        submitting: false,
        submitted: false,

        get submitButtonText() {
            if (this.submitted) return 'Заявка отправлена!';
            else if (this.submitting) return 'Отправка...';
            
            return 'Подписаться';
        },

        submitForm() {
            @if (config('services.yandex.smartcaptcha.enabled'))
                this.$dispatch('execute-captcha', { captchaId: 'subscribe-to-news-captcha' });
            @else
                $wire.submit();
            @endif
        },

        onCaptchaSuccess(event) {
            if (event.detail.captchaId === 'subscribe-to-news-captcha') {
                $wire.submit();
            }
        },

        onFormSubmitted(event) {
            if (event.detail.id === 'subscribe-to-news') {
                this.submitting = false;
                this.submitted = true;
            }
        }
    }"
    x-on:submit.prevent="submitForm"
    x-on:captcha-success.window="onCaptchaSuccess"
    x-on:form-submitted.window="submitting = false"
>
    <x-yandex-smart-captcha
        captcha-id="subscribe-to-news-captcha"
        wire:model="captchaToken"
    />
    <input
        id="subscribeEmail"
        name="email"
        type="email"
        placeholder="E-mail"
        required
        wire:model="email"
        x-bind:disabled="submitting || submitted"
    >
    <button
        class="btn btn--primary footer__btn"
        type="submit"
        x-bind:disabled="submitting || submitted"
        x-text="submitButtonText"
    ></button>
</form>
