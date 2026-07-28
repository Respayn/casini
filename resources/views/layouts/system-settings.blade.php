@php
    use App\Support\ClientsAndProjectsPermissions;
    use App\Support\SystemSettingsSectionPermissions;

    $user = auth()->user();
    $hasAgencies = $user->agencies()->exists();
    $currentAgencyId = session('current_agency_id') ?? (auth()->user()->agency_id ?? null);
    $isAgencyExist = !empty(\App\Models\Agency::query()->find(session('current_agency_id')));

    $canSeeRolesAndPermissions = SystemSettingsSectionPermissions::userCanRead(
        SystemSettingsSectionPermissions::rolesAndPermissions(),
        $user
    );
    $canSeeUsers = SystemSettingsSectionPermissions::userCanRead(
        SystemSettingsSectionPermissions::users(),
        $user
    );
    $canSeeClients = ClientsAndProjectsPermissions::userCanRead($user);
    $canSeeDictionaries = SystemSettingsSectionPermissions::userCanRead(
        SystemSettingsSectionPermissions::dictionaries(),
        $user
    );
    $canSeeAgency = SystemSettingsSectionPermissions::userCanRead(
        SystemSettingsSectionPermissions::agency(),
        $user
    );

    $navbarItems = array_values(array_filter([
        $canSeeRolesAndPermissions
            ? ['label' => 'Продукты и права', 'route' => 'system-settings.roles-and-permissions']
            : null,
        $canSeeUsers
            ? ['label' => 'Пользователи и роли', 'route' => 'system-settings.users']
            : null,
        $canSeeClients
            ? ['label' => 'Клиенты и клиенто-проекты', 'route' => 'system-settings.clients-and-projects']
            : null,
        $canSeeDictionaries
            ? ['label' => 'Справочники', 'route' => 'system-settings.dictionaries']
            : null,
    ]));
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
    <div class="flex w-full flex-col gap-[25px] pl-[375px]">
        <livewire:header />

        <x-menu.navbar :items="$navbarItems">
            {{-- Настройки агенства (с открытием модалки) --}}
            <x-slot:after>
                @if ($canSeeAgency)
                    @if ($isAgencyExist)
                        <x-button.button
                            class="hover:!bg-primary hover:!text-white"
                            :href="route('system-settings.agency')"
                            label="Настройки агентства"
                            :variant="request()->routeIs('system-settings.agency*') ? 'primary' : 'outlined'"
                        />
                    @else
                        <x-button.button
                            class="hover:bg-primary hover:text-white"
                            variant="outlined"
                            label="Настройки агентства"
                            x-data
                            x-on:click="Livewire.dispatch('createIfNotSelected')"
                        />
                    @endif
                @endif
            </x-slot:after>
        </x-menu.navbar>

        <div class="rounded-l-2xl bg-white p-5">
            {{ $slot }}
        </div>
    </div>

    @livewireScriptConfig
</body>

</html>
