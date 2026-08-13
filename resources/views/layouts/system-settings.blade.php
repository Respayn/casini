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

@php
    $settingsNavItemStyle = 'max-width: 11rem; white-space: normal; min-height: 3.625rem; box-sizing: border-box;';
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
    <x-layout.sidebar-boot />
</head>

<body
    class="bg-body text-primary-text font-sans"
    x-data
>
    <livewire:sidebar />

    <div class="app-main flex min-w-0 flex-1 flex-col gap-[25px] pe-[20px]">
        <livewire:header />

        <x-menu.navbar
            :items="$navbarItems"
            align="stretch"
            item-class="box-border !justify-start rounded-lg px-3.5 py-2 text-left leading-5"
            :item-style="$settingsNavItemStyle"
        >
            {{-- Настройки агенства (с открытием модалки) --}}
            <x-slot:after>
                @if ($canSeeAgency)
                    @if ($isAgencyExist)
                        <x-button.button
                            class="box-border !justify-start rounded-lg px-3.5 py-2 text-left leading-5 hover:!bg-primary hover:!text-white"
                            style="{{ $settingsNavItemStyle }}"
                            :href="route('system-settings.agency')"
                            label="Настройки агентства"
                            size="none"
                            :variant="request()->routeIs('system-settings.agency*') ? 'primary' : 'outlined'"
                        />
                    @else
                        <x-button.button
                            class="box-border !justify-start rounded-lg px-3.5 py-2 text-left leading-5 hover:bg-primary hover:text-white"
                            style="{{ $settingsNavItemStyle }}"
                            variant="outlined"
                            label="Настройки агентства"
                            size="none"
                            x-data
                            x-on:click="Livewire.dispatch('createIfNotSelected')"
                        />
                    @endif
                @endif
            </x-slot:after>
        </x-menu.navbar>

        <div class="min-w-0 overflow-x-auto rounded-l-2xl bg-white p-5">
            {{ $slot }}
        </div>
    </div>

    @livewireScriptConfig

    <x-scripts.yandex-direct-oauth-coordinator />
</body>

</html>
