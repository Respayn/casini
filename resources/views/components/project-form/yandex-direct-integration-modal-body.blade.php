@props([
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
])

@php
    use App\Models\Integration;
    use App\Models\IntegrationProject;
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;

    $savedSettings = $projectIntegration->settings ?? [];
    $getSetting = function (string $key, mixed $default = '') use ($savedSettings) {
        if (array_key_exists($key, $savedSettings)) {
            return $savedSettings[$key];
        }

        $camel = Str::camel($key);
        if (array_key_exists($camel, $savedSettings)) {
            return $savedSettings[$camel];
        }

        // Legacy: oauth_token мог сохраняться как encryptedOauthToken без фактического шифрования
        if ($key === 'oauth_token' && array_key_exists('encryptedOauthToken', $savedSettings)) {
            return $savedSettings['encryptedOauthToken'];
        }

        if ($key === 'refresh_token' && array_key_exists('encryptedRefreshToken', $savedSettings)) {
            return $savedSettings['encryptedRefreshToken'];
        }

        if ($key === 'token_expires_at' && array_key_exists('tokenExpiresAt', $savedSettings)) {
            return $savedSettings['tokenExpiresAt'];
        }

        return $default;
    };

    $syncEnabledAt = $getSetting('sync_enabled_at', '');

    if (($projectIntegration->isEnabled ?? false) && $syncEnabledAt === '' && $projectId) {
        $directIntegration = Integration::query()->where('code', 'yandex_direct')->first();

        if ($directIntegration) {
            $integrationRecord = IntegrationProject::query()
                ->where('project_id', $projectId)
                ->where('integration_id', $directIntegration->id)
                ->first();

            if ($integrationRecord?->updated_at) {
                $syncEnabledAt = $integrationRecord->updated_at->format('Y-m-d');
            }
        }
    }

    $directSettings = [
        'is_enabled' => $projectIntegration->isEnabled ?? false,
        'sync_enabled_at' => $syncEnabledAt,
        'client_login' => (string) $getSetting('client_login', ''),
        'oauth_token' => (string) $getSetting('oauth_token', ''),
        'refresh_token' => (string) $getSetting('refresh_token', ''),
        'token_expires_at' => (string) $getSetting('token_expires_at', ''),
        'account_id' => (string) $getSetting('account_id', ''),
    ];
@endphp

<div
    wire:ignore
    class="flex h-full w-fit min-w-0 flex-col"
    x-data="{
        platformConfigured: {{ Js::from($platformConfigured) }},
        settings: {{ Js::from($directSettings) }},
        loginOptions: [],
        loginSelectOpen: false,
        loginsLoading: false,
        loginsError: null,
        oauthError: null,
        oauthPopup: null,
        oauthCacheDataId: null,
        oauthWatchdogTimer: null,
        oauthApplying: false,
        oauthStarting: false,
        oauthPopupOpened: false,
        oauthNavigateCompleted: false,
        oauthPopupWindowName: null,
        oauthBroadcast: null,
        onOAuthAppliedHandler: null,
        onOAuthMessageHandler: null,
        integrationId: {{ (int) $projectIntegration->integration->id }},

        persistOAuthPending() {
            if (!this.oauthCacheDataId) {
                return;
            }

            localStorage.removeItem('casini_yandex_direct_oauth_done');
            localStorage.setItem('casini_yandex_direct_oauth', JSON.stringify({
                cacheDataId: this.oauthCacheDataId,
                ts: Date.now(),
            }));

            if (typeof window.__casiniStartYandexDirectOAuthPolling === 'function') {
                window.__casiniStartYandexDirectOAuthPolling();
            }
        },

        clearOAuthPending() {
            localStorage.removeItem('casini_yandex_direct_oauth');
            localStorage.removeItem('casini_yandex_direct_oauth_done');
        },

        init() {
            this.$watch('settings.is_enabled', (enabled) => {
                if (!enabled) {
                    this.settings.sync_enabled_at = '';
                    return;
                }

                if (!this.platformConfigured) {
                    this.settings.is_enabled = false;
                    return;
                }

                if (!this.settings.oauth_token) {
                    if (this.oauthStarting || this.oauthApplying || this.oauthPopupOpened || this.oauthNavigateCompleted) {
                        return;
                    }

                    this.startOAuth();
                    return;
                }

                if (!this.settings.sync_enabled_at) {
                    this.settings.sync_enabled_at = this.todayIso();
                }
            });

            document.addEventListener('visibilitychange', () => this.onVisibilityChange());

            this.onOAuthAppliedHandler = (event) => this.onOAuthApplied(event);
            window.addEventListener('yandex-direct-oauth-applied', this.onOAuthAppliedHandler);

            this.onOAuthMessageHandler = (event) => this.onOAuthMessage(event);
            window.addEventListener('message', this.onOAuthMessageHandler);

            if (typeof BroadcastChannel !== 'undefined') {
                this.oauthBroadcast = new BroadcastChannel('yandex-direct-oauth');
                this.oauthBroadcast.onmessage = (event) => this.handleOAuthPayload(event.data);
            }

            if (this.settings.oauth_token) {
                this.loadLogins();
            }
        },

        destroy() {
            this.stopOAuthWatchdog();

            if (this.onOAuthAppliedHandler) {
                window.removeEventListener('yandex-direct-oauth-applied', this.onOAuthAppliedHandler);
            }

            if (this.onOAuthMessageHandler) {
                window.removeEventListener('message', this.onOAuthMessageHandler);
            }

            if (this.oauthBroadcast) {
                this.oauthBroadcast.close();
                this.oauthBroadcast = null;
            }
        },

        todayIso() {
            const today = new Date();

            return today.getFullYear()
                + '-' + String(today.getMonth() + 1).padStart(2, '0')
                + '-' + String(today.getDate()).padStart(2, '0');
        },

        extractOAuthUrl(payload) {
            if (!payload) {
                return null;
            }

            if (typeof payload === 'string') {
                return payload;
            }

            if (Array.isArray(payload)) {
                for (const item of payload) {
                    const url = this.extractOAuthUrl(item);
                    if (url) {
                        return url;
                    }
                }

                return null;
            }

            if (typeof payload === 'object') {
                return payload.url
                    ?? payload.detail?.url
                    ?? (Array.isArray(payload.detail) ? this.extractOAuthUrl(payload.detail) : null)
                    ?? null;
            }

            return null;
        },

        extractCacheDataIdFromUrl(url) {
            if (!url) {
                return null;
            }

            try {
                return new URL(url, window.location.origin).searchParams.get('cache_data_id');
            } catch (e) {
                return null;
            }
        },

        withPopupFlag(url, popup) {
            try {
                const parsed = new URL(url, window.location.origin);
                parsed.searchParams.set('popup', popup ? '1' : '0');

                return parsed.toString();
            } catch (e) {
                return url;
            }
        },

        shouldUseRedirectOAuth() {
            if (typeof navigator !== 'undefined' && /Cursor|Electron/i.test(navigator.userAgent || '')) {
                return true;
            }

            try {
                if (window.self !== window.top) {
                    return true;
                }
            } catch (e) {
                return true;
            }

            return false;
        },

        get canSave() {
            if (this.settings.is_enabled && !this.platformConfigured) {
                return false;
            }

            const hasLogin = this.settings.client_login !== ''
                && this.loginOptions.some(o => String(o.value) === String(this.settings.client_login));

            if (!hasLogin) {
                return false;
            }

            if (this.settings.is_enabled && !this.settings.oauth_token) {
                return false;
            }

            return true;
        },

        get syncEnabledLabel() {
            if (!this.settings.sync_enabled_at) {
                return '';
            }

            const [y, m, d] = this.settings.sync_enabled_at.split('-');

            return `включена: ${d}.${m}.${y}`;
        },

        get integrationError() {
            return this.oauthError || this.loginsError || null;
        },

        get hasIntegrationError() {
            return this.integrationError !== null && this.integrationError !== '';
        },

        get loginSelectLabel() {
            if (this.loginsLoading) {
                return 'Загрузка...';
            }

            if (!this.settings.oauth_token) {
                return 'Сначала включите синхронизацию';
            }

            if (this.settings.client_login) {
                const option = this.loginOptions.find(
                    o => String(o.value) === String(this.settings.client_login)
                );

                if (option) {
                    return option.label;
                }
            }

            if (this.loginOptions.length === 0) {
                return 'Нет доступных логинов';
            }

            return 'Выберите логин';
        },

        get loginSelectDisabled() {
            return !this.settings.oauth_token || this.loginsLoading || this.loginOptions.length === 0;
        },

        toggleLoginSelect() {
            if (this.loginSelectDisabled) {
                return;
            }

            this.loginSelectOpen = !this.loginSelectOpen;
        },

        selectLogin(value) {
            this.settings.client_login = String(value);
            this.loginSelectOpen = false;
        },

        stopOAuthWatchdog() {
            if (this.oauthWatchdogTimer) {
                clearTimeout(this.oauthWatchdogTimer);
                this.oauthWatchdogTimer = null;
            }
        },

        startOAuthWatchdog() {
            this.stopOAuthWatchdog();

            this.oauthWatchdogTimer = setTimeout(() => {
                if (this.oauthStarting && !this.oauthNavigateCompleted) {
                    this.failOAuthStart('Не удалось начать авторизацию Яндекс.Директ');
                }
            }, 3000);
        },

        onVisibilityChange() {
            if (document.visibilityState !== 'visible') {
                return;
            }

            if (this.oauthCacheDataId && !this.settings.oauth_token && !this.oauthApplying) {
                if (typeof window.__casiniTryYandexDirectOAuth === 'function') {
                    window.__casiniTryYandexDirectOAuth(this.oauthCacheDataId);
                }
            }
        },

        onOAuthApplied(event) {
            const detail = event?.detail ?? {};
            const settings = detail.settings ?? {};
            const integrationId = detail.integrationId ?? null;

            if (integrationId !== null && Number(integrationId) !== Number(this.integrationId)) {
                return;
            }

            this.applyOAuthSettings(settings, detail.logins ?? [], detail.loginsError ?? null, true);
        },

        handleOAuthPayload(data) {
            if (!data) {
                return;
            }

            if (data.type === 'yandex-direct-oauth-error') {
                this.failOAuthStart(data.error || 'Не удалось завершить авторизацию Яндекс.Директ');
                return;
            }

            if (data.type !== 'yandex-direct-oauth') {
                return;
            }

            if (data.integrationId && Number(data.integrationId) !== Number(this.integrationId)) {
                return;
            }

            this.applyOAuthSettings(data.settings || {});
        },

        onOAuthMessage(event) {
            if (event.origin !== window.location.origin) {
                return;
            }

            this.handleOAuthPayload(event.data);
        },

        failOAuthStart(message) {
            this.stopOAuthWatchdog();
            this.clearOAuthPending();

            if (this.oauthPopup && !this.oauthPopup.closed) {
                this.oauthPopup.close();
            }

            this.oauthPopup = null;
            this.oauthPopupOpened = false;
            this.oauthNavigateCompleted = false;
            this.oauthStarting = false;
            this.settings.is_enabled = false;
            this.oauthError = message;
        },

        navigateOAuthPopup(url) {
            if (!url || this.oauthNavigateCompleted) {
                return;
            }

            this.oauthNavigateCompleted = true;

            try {
                this.oauthError = null;
                this.oauthPopupWindowName = 'yandex-direct-oauth-' + (this.oauthCacheDataId || 'session') + '-' + Date.now();

                this.oauthPopup = window.open(
                    url,
                    this.oauthPopupWindowName,
                    'width=600,height=700,menubar=no,toolbar=no'
                );

                if (!this.oauthPopup) {
                    this.oauthNavigateCompleted = false;
                    window.location.href = this.withPopupFlag(url, false);
                    return;
                }

                this.oauthPopupOpened = true;
                this.oauthStarting = false;
                this.stopOAuthWatchdog();
            } catch (e) {
                console.error('Yandex Direct OAuth popup navigate failed', e);
                this.oauthNavigateCompleted = false;
                this.failOAuthStart('Не удалось начать авторизацию Яндекс.Директ');
            }
        },

        async startOAuth() {
            if (this.oauthStarting || this.oauthPopupOpened || this.oauthNavigateCompleted) {
                return;
            }

            this.oauthError = null;
            this.oauthStarting = true;
            this.oauthNavigateCompleted = false;
            this.oauthCacheDataId = null;
            this.settings.oauth_token = '';
            this.settings.client_login = '';
            this.settings.refresh_token = '';
            this.settings.token_expires_at = '';
            this.settings.account_id = '';
            this.loginOptions = [];
            this.loginsError = null;
            this.stopOAuthWatchdog();

            if (!this.platformConfigured) {
                this.failOAuthStart('Интеграция Яндекс.Директ не настроена на сервере. Обратитесь к администратору.');
                return;
            }

            const useRedirect = this.shouldUseRedirectOAuth();
            this.startOAuthWatchdog();

            try {
                const result = await $wire.prepareYandexDirectOAuth(! useRedirect);

                if (result?.error) {
                    this.failOAuthStart(result.error);
                    return;
                }

                const url = this.extractOAuthUrl(result);
                this.oauthCacheDataId = result?.cache_data_id
                    || this.extractCacheDataIdFromUrl(url);

                if (this.oauthCacheDataId) {
                    this.persistOAuthPending();
                }

                if (!url || !this.oauthCacheDataId) {
                    this.oauthNavigateCompleted = false;
                    this.failOAuthStart('Не удалось начать авторизацию Яндекс.Директ');
                    return;
                }

                if (useRedirect) {
                    this.oauthNavigateCompleted = true;
                    this.oauthStarting = false;
                    this.stopOAuthWatchdog();
                    window.location.href = this.withPopupFlag(url, false);
                    return;
                }

                this.navigateOAuthPopup(this.withPopupFlag(url, true));
            } catch (e) {
                console.error('Yandex Direct OAuth start failed', e);

                if (!this.oauthNavigateCompleted) {
                    this.failOAuthStart('Не удалось начать авторизацию Яндекс.Директ');
                }
            }
        },

        async applyOAuthSettings(oauthSettings, logins = null, loginsError = null, fromServer = false) {
            if (this.oauthApplying) {
                return;
            }

            if (this.settings.oauth_token && oauthSettings?.oauth_token === this.settings.oauth_token) {
                if (Array.isArray(logins) && logins.length > 0) {
                    this.loginOptions = logins;
                    this.loginsError = loginsError;
                }

                return;
            }

            this.oauthApplying = true;
            this.oauthStarting = false;
            this.oauthPopupOpened = false;
            this.oauthNavigateCompleted = false;
            this.clearOAuthPending();
            this.stopOAuthWatchdog();

            this.settings.oauth_token = oauthSettings.oauth_token || '';
            this.settings.refresh_token = oauthSettings.refresh_token || '';
            this.settings.token_expires_at = oauthSettings.token_expires_at || '';
            this.settings.account_id = oauthSettings.account_id || '';
            this.settings.client_login = '';
            this.settings.is_enabled = true;
            this.settings.sync_enabled_at = oauthSettings.sync_enabled_at || this.todayIso();
            this.oauthError = null;

            if (this.oauthPopup && !this.oauthPopup.closed) {
                this.oauthPopup.close();
            }

            if (!fromServer) {
                try {
                    await $wire.applyYandexDirectOAuthTokens({
                        oauth_token: this.settings.oauth_token,
                        refresh_token: this.settings.refresh_token,
                        token_expires_at: this.settings.token_expires_at,
                        account_id: this.settings.account_id,
                    });
                } catch (e) {
                    // Alpine state already updated; Livewire sync is best-effort
                }
            }

            if (Array.isArray(logins) && logins.length > 0) {
                this.loginOptions = logins;
                this.loginsError = loginsError;
                this.oauthError = null;
            } else if (loginsError) {
                this.loginOptions = [];
                this.loginsError = loginsError;
            } else {
                await this.loadLogins();
            }

            this.oauthApplying = false;
        },

        async loadLogins() {
            if (!this.settings.oauth_token) {
                this.loginOptions = [];
                return;
            }

            this.loginsLoading = true;
            this.loginsError = null;

            try {
                const result = await $wire.loadYandexDirectLogins(this.settings.oauth_token);

                if (result.error) {
                    this.loginsError = result.error;
                    this.loginOptions = [];
                } else {
                    this.loginsError = null;
                    this.oauthError = null;
                    this.loginOptions = result.logins ?? [];

                    if (
                        this.settings.client_login
                        && !this.loginOptions.some(o => String(o.value) === String(this.settings.client_login))
                    ) {
                        this.settings.client_login = '';
                    }
                }
            } catch (e) {
                this.loginsError = 'Не удалось загрузить логины Яндекс.Директ';
                this.loginOptions = [];
            }

            this.loginsLoading = false;
        },

        save() {
            if (!this.canSave) {
                return;
            }

            const payload = { ...this.settings };

            if (!payload.is_enabled) {
                delete payload.sync_enabled_at;
            } else if (!payload.sync_enabled_at) {
                payload.sync_enabled_at = this.todayIso();
            }

            $wire.setIntegrationSettings(this.integrationId, payload);
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        handleCancelClick() {
            this.stopOAuthWatchdog();
            this.oauthStarting = false;
            this.oauthPopupOpened = false;
            this.oauthNavigateCompleted = false;
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },
    }"
>
    <x-form.form class="mb-7 lg:min-w-[580px]">
        @unless ($platformConfigured)
            <p class="text-warning-red mb-4 text-sm">
                Интеграция Яндекс.Директ не настроена на сервере. Обратитесь к администратору.
            </p>
        @endunless

        <div
            class="mb-4 break-words rounded-lg border p-4 text-sm"
            style="border-color: #FF7373; color: #FF7373; background-color: #FFF5F5;"
            x-show="hasIntegrationError"
            x-text="integrationError"
            x-cloak
        ></div>

        <x-form.form-field>
            <x-form.form-label
                class="self-baseline"
                tooltip="Ползунок синхронизации аккаунта Яндекс.Директ"
            >
                Синхронизация
            </x-form.form-label>
            <div class="flex w-[305px] items-center gap-4">
                <div :class="{ 'yd-direct-error-toggle': hasIntegrationError && settings.is_enabled }">
                    <x-form.toggle-switch x-model="settings.is_enabled"></x-form.toggle-switch>
                </div>
                <span
                    class="text-secondary-text text-sm"
                    x-show="settings.is_enabled && settings.sync_enabled_at"
                    x-text="syncEnabledLabel"
                    x-cloak
                ></span>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label
                required
                tooltip="Логин рекламодателя в Яндекс.Директе (Client-Login)"
            >
                Логин
            </x-form.form-label>
            <div class="w-[305px]">
                <div class="text-input-text relative select-none">
                    <div class="group" x-ref="loginSelectButton">
                        <div
                            class="border-input-border flex min-h-[42px] w-full items-center rounded-[5px] border pe-10 ps-4"
                            x-ref="loginSelectTrigger"
                            x-on:click="toggleLoginSelect()"
                            x-bind:class="{
                                'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': loginSelectOpen,
                                'rounded-[5px]': !loginSelectOpen,
                                'yd-direct-error-login': hasIntegrationError,
                                'bg-secondary': loginSelectDisabled && !hasIntegrationError,
                                'opacity-70': !loginsLoading && !settings.oauth_token
                            }"
                        >
                            <span
                                class="overflow-hidden"
                                x-text="loginSelectLabel"
                                x-bind:class="{
                                    'opacity-50': settings.oauth_token && !settings.client_login && loginOptions.length > 0,
                                    'text-gray-400 italic': !settings.oauth_token || loginOptions.length === 0
                                }"
                            ></span>
                        </div>

                        <template x-if="!loginSelectDisabled && loginOptions.length > 0">
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                                <x-icons.arrow
                                    class="transition-transform duration-300"
                                    x-bind:class="{
                                        'rotate-180 group-hover:text-white': loginSelectOpen,
                                    }"
                                />
                            </span>
                        </template>
                    </div>

                    <div
                        class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0"
                        x-cloak
                        x-show="loginSelectOpen && loginOptions.length > 0"
                        x-anchor.no-style="$refs.loginSelectButton"
                        x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
                        x-on:click.outside="loginSelectOpen = false"
                    >
                        <template x-for="option in loginOptions" :key="option.value">
                            <div
                                class="hover:bg-primary flex min-h-[42px] cursor-pointer items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                                x-on:click="selectLogin(option.value)"
                                x-text="option.label"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </x-form.form-field>
    </x-form.form>

    <x-project-form.integration-modal-footer />
</div>
