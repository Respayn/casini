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

<body class="min-h-screen font-sans antialiased">
    <div class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-md flex-col gap-24">
            <span class="flex items-center justify-center rounded-md">
                <x-icons.logo width="230" />
            </span>

            <div class="flex flex-col gap-6">
                <div class="shadow-xs rounded-[14px] border border-[#CFDFF4] bg-white text-stone-800">
                    <div class="p-12">{{ $slot }}</div>
                </div>
            </div>
        </div>
    </div>
    @livewireScripts
</body>

</html>
