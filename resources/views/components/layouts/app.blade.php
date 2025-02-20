<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Page Title' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body>
        <body class="flex gap-5 font-sans bg-body">
            <livewire:sidebar />
            
            <div class="flex flex-col w-full gap-[25px]">
                <livewire:header />

                <x-menu.breadcrumb />

                <div class="p-5 bg-white rounded-l-2xl">
                    {{ $slot }}
                </div>
            </div>

            @livewireScripts
        </body>
    </body>
</html>