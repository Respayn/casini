<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Google Таблицы — авторизация</title>
</head>
<body>
    <p id="oauth-status">Авторизация завершена. Это окно можно закрыть.</p>
    <script>
        (function () {
            const cacheDataId = @json($cacheDataId ?? null);
            const payload = {
                type: 'google-sheets-oauth',
                integrationId: {{ (int) $integrationId }},
                cacheDataId: cacheDataId,
                settings: @json($settings),
            };

            try {
                if (cacheDataId) {
                    localStorage.setItem('casini_google_sheets_oauth_done', cacheDataId);
                    localStorage.setItem('casini_google_sheets_oauth', JSON.stringify({
                        cacheDataId: cacheDataId,
                        ts: Date.now(),
                    }));
                }
            } catch (e) {
            }

            try {
                if (typeof BroadcastChannel !== 'undefined') {
                    const channel = new BroadcastChannel('google-sheets-oauth');
                    channel.postMessage(payload);
                    channel.close();
                }
            } catch (e) {
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
