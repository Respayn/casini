<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $title ?? 'Page Title' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-body text-primary-text flex gap-5 font-sans">
    <livewire:sidebar />

    <div class="flex w-full flex-col gap-[25px] pl-[375px]">
        <livewire:header />

        {{-- <x-menu.breadcrumb /> --}}

        <x-menu.navbar :items="[]" />

        <div class="rounded-l-2xl bg-white p-5">
            {{ $slot }}
        </div>
    </div>

    <x-toaster-hub />

    @livewireScripts
</body>

</html>
