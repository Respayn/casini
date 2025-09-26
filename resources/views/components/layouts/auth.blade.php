@props(['title' => null])

    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Page Title' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="bg-muted min-h-screen flex flex-col items-center pt-[50px]">
        <span class="flex items-center justify-center mb-12">
          <x-icons.logo width="230" />
        </span>

        <div class="flex-1 w-full flex justify-center px-4">
            <div class="w-full max-w-md">
                <div class="shadow-xs rounded-[14px] border border-[#CFDFF4] bg-white text-stone-800">
                    <div class="p-12">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    <x-toaster-hub />

    @livewireScriptConfig
</body>
</html>
