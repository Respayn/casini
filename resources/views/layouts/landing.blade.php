<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $title ?? 'Page Title' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon_graph_v4.png">
    <link rel="icon" type="image/x-icon" href="images/favicon_graph_v4.ico">
    <link rel="shortcut icon" href="images/favicon_graph_v4.ico">
    <link rel="apple-touch-icon" href="images/favicon_graph_v4.png">

    <!-- Yandex SmartCaptcha -->
    <script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>

    @vite(['resources/css/landing.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    {{ $slot }}

    @livewireScriptConfig
</body>

</html>
