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

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon_graph_v4.png">
    <link rel="icon" type="image/x-icon" href="images/favicon_graph_v4.ico">
    <link rel="shortcut icon" href="images/favicon_graph_v4.ico">
    <link rel="apple-touch-icon" href="images/favicon_graph_v4.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-body text-primary-text flex gap-5 font-sans">
    <livewire:sidebar />

    <div class="flex w-full flex-col gap-[25px] pl-[375px]">
        <livewire:header />

        <x-menu.navbar :items="[
            ['label' => 'Каналы', 'route' => 'channels']
        ]" />

        <div class="rounded-l-2xl bg-white p-5">
            {{ $slot }}
        </div>
    </div>

    <x-toaster-hub />

    @livewireScriptConfig 
</body>

</html>
