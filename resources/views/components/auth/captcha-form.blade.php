@props([
    'captchaId',
    'wireMethod',
])

@php
    $captchaEnabled = (bool) config('services.yandex.smartcaptcha.enabled');
@endphp

{{-- Do not put @if/@json inside attributes of <x-*> components — Blade leaves them literal. --}}
<form
    {{ $attributes->class([
        'form',
        'text-primary-text',
        'flex',
        'flex-col',
        'gap-5',
    ]) }}
    x-data="{
        submitForm() {
            if ({{ $captchaEnabled ? 'true' : 'false' }}) {
                this.$dispatch('execute-captcha', { captchaId: '{{ $captchaId }}' });
            } else {
                $wire.{{ $wireMethod }}();
            }
        },
        onCaptchaSuccess(event) {
            if (event.detail.captchaId !== '{{ $captchaId }}') {
                return;
            }

            $wire.set('captchaToken', event.detail.token).then(() => {
                $wire.{{ $wireMethod }}();
            });
        },
    }"
    x-on:submit.prevent="submitForm"
    x-on:captcha-success.window="onCaptchaSuccess"
>
    <x-yandex-smart-captcha
        captcha-id="{{ $captchaId }}"
        wire:model="captchaToken"
    />

    {{ $slot }}
</form>
