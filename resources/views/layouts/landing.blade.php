<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $title ?? 'Page Title' }}</title>

    <x-layout.favicon />

    @if (config('services.yandex.smartcaptcha.enabled'))
        <script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>
    @endif

    @vite(['resources/css/landing.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    {{ $slot }}

    @livewireScriptConfig
</body>

</html>
