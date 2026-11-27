<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <x-layout.document-head-brand title="Яндекс Метрика: авторизация" />
    <x-layout.favicon />
</head>
<body>
    <p>{{ $message }}</p>
    <script>
        (function () {
            const payload = {
                type: 'yandex-metrika-oauth-error',
                error: @json($message),
            };

            try {
                if (typeof BroadcastChannel !== 'undefined') {
                    const channel = new BroadcastChannel('yandex-metrika-oauth');
                    channel.postMessage(payload);
                    channel.close();
                }
            } catch (e) {
                // BroadcastChannel unavailable — rely on postMessage
            }

            if (window.opener && !window.opener.closed) {
                window.opener.postMessage(payload, window.location.origin);
            }

            setTimeout(function () {
                window.close();
            }, 1500);
        })();
    </script>
</body>
</html>
