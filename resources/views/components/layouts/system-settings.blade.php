@php
    $user = auth()->user();
    $hasAgencies = $user->agencies()->exists();
    $currentAgencyId = session('current_agency_id') ?? (auth()->user()->agency_id ?? null);
    $isAgencyExist = !empty(\App\Models\AgencySetting::query()->find(session('current_agency_id')));
@endphp

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

<body class="bg-body flex gap-5 font-sans text-primary-text">
    <livewire:sidebar />

    <div class="flex w-full flex-col gap-[25px] pl-[375px]">
        <livewire:header />

        <x-menu.navbar :items="[
            ['label' => 'Клиенты и клиенто-проекты', 'route' => 'system-settings.clients-and-projects'],
            ['label' => 'Справочники', 'route' => 'system-settings.dictionaries'],
        ]">
            {{-- Настройки агенства (с открытием модалки) --}}
            <x-slot:after>
                @if($isAgencyExist)
                    <x-button.button
                        :href="route('system-settings.agency')"
                        label="Настройки агентства"
                        class="hover:!bg-primary hover:!text-white"
                        :variant="request()->routeIs('system-settings.agency*') ? 'primary' : 'outlined'"
                    />
                @else
                    <x-button.button
                        variant="outlined"
                        label="Настройки агентства"
                        class="hover:bg-primary hover:text-white"
                        x-data
                        x-on:click="Livewire.dispatch('modal-show', { name: 'agency-modal' })"
                    />
                @endif
            </x-slot:after>
        </x-menu.navbar>

        <div class="rounded-l-2xl bg-white p-5">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>

</html>
