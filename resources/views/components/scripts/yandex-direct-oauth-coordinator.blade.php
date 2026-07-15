<script>
    (function () {
        const STORAGE_KEY = 'casini_yandex_direct_oauth';
        const DONE_KEY = 'casini_yandex_direct_oauth_done';
        const COMPONENT_NAME = 'pages::system-settings.client-project-form';
        let pollTimer = null;
        let applying = false;

        const getFormWire = () => {
            if (typeof Livewire === 'undefined') {
                return null;
            }

            const components = Livewire.getByName(COMPONENT_NAME);

            return components?.length ? components[0] : null;
        };

        const clearPending = () => {
            localStorage.removeItem(STORAGE_KEY);
            localStorage.removeItem(DONE_KEY);

            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        };

        const getPending = () => {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);

                if (!raw) {
                    return null;
                }

                const data = JSON.parse(raw);

                if (!data?.cacheDataId || Date.now() - (data.ts || 0) > 15 * 60 * 1000) {
                    clearPending();

                    return null;
                }

                return data;
            } catch (e) {
                return null;
            }
        };

        const getDoneCacheDataId = () => {
            try {
                return localStorage.getItem(DONE_KEY);
            } catch (e) {
                return null;
            }
        };

        const tryApplyOAuthPayload = async (data) => {
            if (applying || !data) {
                return false;
            }

            applying = true;

            try {
                const wire = getFormWire();
                const hasSettings = data.settings && Object.keys(data.settings).length > 0;
                const cacheDataId = data.cacheDataId || null;

                if (wire) {
                    if (hasSettings) {
                        await wire.applyYandexDirectOAuthFromBroadcast(
                            data.settings,
                            data.integrationId ?? null
                        );
                        clearPending();
                        applying = false;

                        return true;
                    }

                    if (cacheDataId) {
                        const result = await wire.finalizeYandexDirectOAuth(cacheDataId);

                        if (result?.applied) {
                            clearPending();
                            applying = false;

                            return true;
                        }
                    }
                } else if (typeof Livewire !== 'undefined') {
                    Livewire.dispatchTo(COMPONENT_NAME, 'yandex-direct-oauth-received', {
                        settings: hasSettings ? data.settings : null,
                        cacheDataId: hasSettings ? null : cacheDataId,
                        integrationId: data.integrationId ?? null,
                    });

                    if (hasSettings || cacheDataId) {
                        clearPending();
                        applying = false;

                        return true;
                    }
                }
            } catch (e) {
                console.error('Yandex Direct OAuth apply failed', e);
            }

            applying = false;

            return false;
        };

        const handleOAuthPayload = async (data) => {
            if (!data?.type) {
                return;
            }

            if (data.type === 'yandex-direct-oauth-error') {
                clearPending();

                return;
            }

            if (data.type !== 'yandex-direct-oauth') {
                return;
            }

            await tryApplyOAuthPayload(data);
        };

        const pollOnce = async () => {
            const pending = getPending();
            const doneId = getDoneCacheDataId();

            if (doneId && (!pending || doneId !== pending.cacheDataId)) {
                try {
                    localStorage.removeItem(DONE_KEY);
                } catch (e) {
                    // ignore
                }
            }

            if (doneId && pending && doneId === pending.cacheDataId) {
                const applied = await tryApplyOAuthPayload({
                    type: 'yandex-direct-oauth',
                    cacheDataId: doneId,
                    settings: {},
                });

                if (applied) {
                    return;
                }
            }

            if (!pending) {
                return;
            }

            await tryApplyOAuthPayload({
                type: 'yandex-direct-oauth',
                cacheDataId: pending.cacheDataId,
                settings: {},
            });
        };

        const startPolling = () => {
            if (pollTimer) {
                return;
            }

            pollTimer = setInterval(() => {
                pollOnce();
            }, 800);

            setTimeout(() => {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }, 120000);
        };

        const bindListeners = () => {
            if (typeof BroadcastChannel !== 'undefined') {
                const channel = new BroadcastChannel('yandex-direct-oauth');
                channel.onmessage = (event) => handleOAuthPayload(event.data);
            }

            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin) {
                    return;
                }

                handleOAuthPayload(event.data);
            });

            window.addEventListener('storage', (event) => {
                if (event.key !== DONE_KEY && event.key !== STORAGE_KEY) {
                    return;
                }

                pollOnce();
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState !== 'visible') {
                    return;
                }

                pollOnce();
            });

            if (getPending() || getDoneCacheDataId()) {
                pollOnce();
                startPolling();
            }
        };

        window.__casiniStartYandexDirectOAuthPolling = startPolling;
        window.__casiniTryYandexDirectOAuth = (cacheDataId) => tryApplyOAuthPayload({
            type: 'yandex-direct-oauth',
            cacheDataId,
            settings: {},
        });

        if (window.Livewire?.initialRenderIsFinished) {
            bindListeners();
        } else {
            document.addEventListener('livewire:init', bindListeners);
        }
    })();
</script>
