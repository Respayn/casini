<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Яндекс Метрика — авторизация</title>
</head>
<body>
    <p id="oauth-status">Авторизация завершена. Это окно можно закрыть.</p>
    <script>
        (function () {
            const cacheDataId = @json($cacheDataId ?? null);
            const payload = {
                type: 'yandex-metrika-oauth',
                integrationId: {{ (int) $integrationId }},
                cacheDataId: cacheDataId,
                settings: @json($settings),
            };

            try {
                if (cacheDataId) {
                    localStorage.setItem('casini_yandex_metrika_oauth_done', cacheDataId);
                    localStorage.setItem('casini_yandex_metrika_oauth', JSON.stringify({
                        cacheDataId: cacheDataId,
                        ts: Date.now(),
                    }));
                }
            } catch (e) {
                // localStorage unavailable — rely on BroadcastChannel / postMessage
            }

            try {
                if (typeof BroadcastChannel !== 'undefined') {
                    const channel = new BroadcastChannel('yandex-metrika-oauth');
                    channel.postMessage(payload);
                    channel.close();
                }
            } catch (e) {
                // BroadcastChannel unavailable — rely on postMessage / polling
            }

            const hasOpener = window.opener && !window.opener.closed;
            const status = document.getElementById('oauth-status');

            if (hasOpener) {
                window.opener.postMessage(payload, window.location.origin);
            } else if (status) {
                status.textContent = 'Авторизация завершена. Вернитесь в окно Касини — авторизация подтянется автоматически. Это окно можно закрыть.';
            }

            setTimeout(function () {
                window.close();

                if (!window.closed && status) {
                    status.textContent = 'Авторизация завершена. Это окно можно закрыть вручную.';
                }
            }, 3000);
        })();
    </script>
</body>
</html>
