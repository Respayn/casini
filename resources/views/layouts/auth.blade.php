@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Page Title' }}</title>
    <x-layout.favicon />

    @if (config('services.yandex.smartcaptcha.enabled'))
        <script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="bg-muted flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center gap-2">
                <x-icons.logo width="230" />
                <x-brand.tagline class="text-center" />
            </div>

            {{ $slot }}
        </div>
    </div>

    @livewireScriptConfig
</body>
</html>
