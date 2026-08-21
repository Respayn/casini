@php
    $user = auth()->user();
    $hasAgencies = $user->agencies()->exists();
    $currentAgencyId = session('current_agency_id') ?? (auth()->user()->agency_id ?? null);
    $isAgencyExist = !empty(\App\Models\Agency::query()->find(session('current_agency_id')));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Page Title' }}</title>

    <x-layout.favicon />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <x-form.checkbox-styles />
</head>

<body class="bg-body text-primary-text flex gap-5 font-sans">
    <livewire:sidebar />

    <div class="flex w-full flex-col gap-[25px] pl-[375px] h-screen">
        <livewire:header />

        <x-menu.navbar :items="collect([
            ['label' => 'Каналы', 'route' => 'channels', 'permissions' => ['read channels', 'full channels']],
            ['label' => 'Статистика', 'route' => 'statistics', 'permissions' => ['read statistics', 'full statistics']],
            ['label' => 'Планирование', 'route' => 'planning', 'permissions' => ['read planning', 'full planning']],
            ['label' => 'Отчеты', 'route' => 'reports', 'permissions' => ['read reports', 'full reports']],
        ])->map(function (array $item) {
            $item['canAccess'] = collect($item['permissions'])
                ->contains(fn (string $permission) => auth()->user()?->can($permission));

            return $item;
        })->values()->all()" />

        <div class="rounded-tl-2xl bg-white p-5 flex-1">
            {{ $slot }}
        </div>
    </div>

    @livewireScriptConfig 
</body>

</html>
