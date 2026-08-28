@props([
    'canEdit' => true,
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

        return $savedSettings[Str::camel($key)] ?? $default;
    };

    $syncEnabledAt = $getSetting('sync_enabled_at', '');

    if (($projectIntegration->isEnabled ?? false) && $syncEnabledAt === '' && $projectId) {
        $integration = Integration::query()->where('code', 'google_sheets')->first();

        if ($integration) {
            $integrationRecord = IntegrationProject::query()
                ->where('project_id', $projectId)
                ->where('integration_id', $integration->id)
                ->first();

            if ($integrationRecord?->updated_at) {
                $syncEnabledAt = $integrationRecord->updated_at->format('Y-m-d');
            }
        }
    }

    $googleSettings = [
        'is_enabled' => $projectIntegration->isEnabled ?? false,
        'sync_enabled_at' => $syncEnabledAt,
        'document_id' => (string) $getSetting('document_id', ''),
        'oauth_token' => (string) $getSetting('oauth_token', ''),
        'refresh_token' => (string) $getSetting('refresh_token', ''),
        'token_expires_at' => (string) $getSetting('token_expires_at', ''),
        'oauth_google_user_id' => (string) $getSetting('oauth_google_user_id', ''),
        'oauth_google_email' => (string) $getSetting('oauth_google_email', ''),
        'oauth_google_display_name' => (string) $getSetting('oauth_google_display_name', ''),
        'oauth_google_avatar_url' => (string) $getSetting('oauth_google_avatar_url', ''),
    ];
@endphp

<div
    wire:ignore
    class="flex h-full w-fit min-w-0 flex-col"
    x-data="{
        canEdit: @js($canEdit),
        platformConfigured: {{ Js::from($platformConfigured) }},
        settings: {{ Js::from($googleSettings) }},
        oauthError: null,
        oauthPopup: null,
        oauthCacheDataId: null,
        oauthWatchdogTimer: null,
        oauthApplying: false,
        oauthStarting: false,
        oauthPopupOpened: false,
        oauthNavigateCompleted: false,
        oauthBroadcast: null,
        oauthAvatarFailed: false,
        onOAuthAppliedHandler: null,
        onOAuthMessageHandler: null,
        integrationId: {{ (int) $projectIntegration->integration->id }},

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
            window.addEventListener('google-sheets-oauth-applied', this.onOAuthAppliedHandler);

            this.onOAuthMessageHandler = (event) => this.onOAuthMessage(event);
            window.addEventListener('message', this.onOAuthMessageHandler);

            if (typeof BroadcastChannel !== 'undefined') {
                this.oauthBroadcast = new BroadcastChannel('google-sheets-oauth');
                this.oauthBroadcast.onmessage = (event) => this.handleOAuthPayload(event.data);
            }

            this.syncOAuthFromServer();
        },

        destroy() {
            this.stopOAuthWatchdog();

            if (this.onOAuthAppliedHandler) {
                window.removeEventListener('google-sheets-oauth-applied', this.onOAuthAppliedHandler);
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

        get canSave() {
            if (this.settings.is_enabled && !this.platformConfigured) {
                return false;
            }

            if (this.settings.document_id.trim() === '') {
                return false;
            }

            if (this.settings.is_enabled && !this.settings.oauth_token) {
                return false;
            }

            return true;
        },

        get integrationError() {
            return this.oauthError || null;
        },

        get hasIntegrationError() {
            return this.integrationError !== null && this.integrationError !== '';
        },

        get syncEnabledLabel() {
            if (!this.settings.sync_enabled_at) {
                return '';
            }

            const [y, m, d] = this.settings.sync_enabled_at.split('-');

            return `включена: ${d}.${m}.${y}`;
        },

        get oauthProfileLabel() {
            return this.settings.oauth_google_display_name
                || this.settings.oauth_google_email
                || 'Google аккаунт';
        },

        get oauthProfileInitial() {
            const label = this.oauthProfileLabel;

            return label ? label.charAt(0).toUpperCase() : 'G';
        },

        get showOAuthProfile() {
            return Boolean(this.settings.oauth_token && this.oauthProfileLabel);
        },

        extractOAuthUrl(payload) {
            if (!payload) {
                return null;
            }

            if (typeof payload === 'string') {
                return payload;
            }

            return payload.url ?? null;
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

        async syncOAuthFromServer() {
            if (this.settings.oauth_token || this.oauthApplying) {
                return;
            }

            try {
                const result = await $wire.getGoogleSheetsOAuthUiState();

                if (result?.settings?.oauth_token) {
                    this.applyOAuthSettings(result.settings, true);
                }
            } catch (e) {
            }
        },

        persistOAuthPending() {
            if (!this.oauthCacheDataId) {
                return;
            }

            localStorage.removeItem('casini_google_sheets_oauth_done');
            localStorage.setItem('casini_google_sheets_oauth', JSON.stringify({
                cacheDataId: this.oauthCacheDataId,
                ts: Date.now(),
            }));

            if (typeof window.__casiniStartGoogleSheetsOAuthPolling === 'function') {
                window.__casiniStartGoogleSheetsOAuthPolling();
            }
        },

        clearOAuthPending() {
            localStorage.removeItem('casini_google_sheets_oauth');
            localStorage.removeItem('casini_google_sheets_oauth_done');
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
                    this.failOAuthStart('Не удалось начать авторизацию Google Таблицы');
                }
            }, 3000);
        },

        async onVisibilityChange() {
            if (document.visibilityState !== 'visible') {
                return;
            }

            await this.syncOAuthFromServer();

            if (this.oauthCacheDataId && !this.settings.oauth_token && !this.oauthApplying) {
                if (typeof window.__casiniTryGoogleSheetsOAuth === 'function') {
                    window.__casiniTryGoogleSheetsOAuth(this.oauthCacheDataId);
                }
            }

            if (!this.settings.oauth_token) {
                this.oauthNavigateCompleted = false;
                this.oauthPopupOpened = false;
            }
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

        async startOAuth() {
            if (!this.canEdit || this.oauthStarting || this.oauthPopupOpened || this.oauthNavigateCompleted) {
                return;
            }

            this.oauthStarting = true;
            this.oauthError = null;
            this.oauthNavigateCompleted = false;
            this.oauthCacheDataId = null;
            this.startOAuthWatchdog();

            const useRedirect = this.shouldUseRedirectOAuth();

            try {
                const result = await $wire.prepareGoogleSheetsOAuth(! useRedirect);

                if (result.error) {
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
                    this.failOAuthStart('Не удалось начать авторизацию Google Таблицы');

                    return;
                }

                if (useRedirect) {
                    this.oauthNavigateCompleted = true;
                    this.oauthStarting = false;
                    this.stopOAuthWatchdog();
                    window.location.href = this.withPopupFlag(url, false);

                    return;
                }

                const width = 520;
                const height = 680;
                const left = window.screenX + (window.outerWidth - width) / 2;
                const top = window.screenY + (window.outerHeight - height) / 2;
                const popupName = 'google-sheets-oauth-' + (this.oauthCacheDataId || 'session') + '-' + Date.now();

                this.oauthPopup = window.open(
                    this.withPopupFlag(url, true),
                    popupName,
                    `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no`
                );

                if (!this.oauthPopup) {
                    this.oauthNavigateCompleted = true;
                    this.oauthStarting = false;
                    this.stopOAuthWatchdog();
                    window.location.href = this.withPopupFlag(url, false);

                    return;
                }

                this.oauthPopupOpened = true;
                this.oauthNavigateCompleted = true;
                this.oauthStarting = false;
                this.stopOAuthWatchdog();
            } catch (e) {
                this.failOAuthStart('Не удалось начать авторизацию Google Таблицы');
            }
        },

        reauthorizeOAuthAccount() {
            this.settings.oauth_token = '';
            this.settings.refresh_token = '';
            this.settings.token_expires_at = '';
            this.settings.oauth_google_user_id = '';
            this.settings.oauth_google_email = '';
            this.settings.oauth_google_display_name = '';
            this.settings.oauth_google_avatar_url = '';
            this.oauthAvatarFailed = false;
            this.startOAuth();
        },

        onOAuthApplied(event) {
            const detail = event?.detail ?? {};
            const settings = detail.settings ?? detail[0]?.settings ?? {};
            const integrationId = detail.integrationId ?? detail[0]?.integrationId ?? null;

            if (integrationId !== null && Number(integrationId) !== Number(this.integrationId)) {
                return;
            }

            this.applyOAuthSettings(settings, true);
        },

        handleOAuthPayload(data) {
            if (!data) {
                return;
            }

            if (data.type === 'google-sheets-oauth-error') {
                this.failOAuthStart(data.error || 'Не удалось завершить авторизацию Google.');
                return;
            }

            if (data.type !== 'google-sheets-oauth') {
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

        async applyOAuthSettings(oauthSettings, fromServer = false) {
            if (this.oauthApplying) {
                return;
            }

            if (this.settings.oauth_token && oauthSettings?.oauth_token === this.settings.oauth_token) {
                if (oauthSettings.oauth_google_email || oauthSettings.oauth_google_display_name) {
                    this.settings.oauth_google_user_id = oauthSettings.oauth_google_user_id || '';
                    this.settings.oauth_google_email = oauthSettings.oauth_google_email || '';
                    this.settings.oauth_google_display_name = oauthSettings.oauth_google_display_name || '';
                    this.settings.oauth_google_avatar_url = oauthSettings.oauth_google_avatar_url || '';
                    this.oauthAvatarFailed = false;
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
            this.settings.oauth_google_user_id = oauthSettings.oauth_google_user_id || '';
            this.settings.oauth_google_email = oauthSettings.oauth_google_email || '';
            this.settings.oauth_google_display_name = oauthSettings.oauth_google_display_name || '';
            this.settings.oauth_google_avatar_url = oauthSettings.oauth_google_avatar_url || '';
            this.oauthAvatarFailed = false;
            this.settings.document_id = oauthSettings.document_id || this.settings.document_id || '';
            this.settings.is_enabled = true;
            this.settings.sync_enabled_at = oauthSettings.sync_enabled_at || this.todayIso();
            this.oauthError = null;

            if (this.oauthPopup && !this.oauthPopup.closed) {
                this.oauthPopup.close();
            }

            if (!fromServer) {
                try {
                    await $wire.applyGoogleSheetsOAuthTokens({
                        oauth_token: this.settings.oauth_token,
                        refresh_token: this.settings.refresh_token,
                        token_expires_at: this.settings.token_expires_at,
                        oauth_google_user_id: this.settings.oauth_google_user_id,
                        oauth_google_email: this.settings.oauth_google_email,
                        oauth_google_display_name: this.settings.oauth_google_display_name,
                        oauth_google_avatar_url: this.settings.oauth_google_avatar_url,
                    });
                } catch (e) {
                    // Alpine state already updated; Livewire sync is best-effort
                }
            }

            this.oauthApplying = false;
        },

        save() {
            if (!this.canEdit || !this.canSave) {
                return;
            }

            const payload = { ...this.settings };

            if (!payload.is_enabled) {
                delete payload.sync_enabled_at;
            } else if (!payload.sync_enabled_at) {
                payload.sync_enabled_at = this.todayIso();
            }

            $wire.setIntegrationSettings({{ $projectIntegration->integration->id }}, payload);
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        handleCancelClick() {
            this.stopOAuthWatchdog();
            this.oauthStarting = false;
            this.oauthPopupOpened = false;
            this.oauthNavigateCompleted = false;
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        normalizeDocumentId(value) {
            const trimmed = String(value ?? '').trim();

            if (trimmed === '') {
                return '';
            }

            const editMatch = trimmed.match(/\/d\/([a-zA-Z0-9\-_]+)\/edit/i);

            if (editMatch) {
                return editMatch[1];
            }

            const spreadsheetsMatch = trimmed.match(/\/spreadsheets\/d\/([a-zA-Z0-9\-_]+)/i);

            if (spreadsheetsMatch) {
                return spreadsheetsMatch[1];
            }

            return trimmed;
        },

        onDocumentIdPaste() {
            this.$nextTick(() => {
                this.settings.document_id = this.normalizeDocumentId(this.settings.document_id);
            });
        },

        onDocumentIdBlur() {
            this.settings.document_id = this.normalizeDocumentId(this.settings.document_id);
        },
    }"
>
    <x-form.form class="mb-7 lg:min-w-[580px]">
        @unless ($platformConfigured)
            <p class="text-warning-red mb-4 text-sm">
                Интеграция Google Таблицы не настроена на сервере. Обратитесь к администратору.
            </p>
        @endunless

        <div
            class="mb-4 break-words rounded-lg border p-4 text-sm"
            style="border-color: #FF7373; color: #FF7373; background-color: #FFF5F5;"
            x-show="hasIntegrationError"
            x-text="integrationError"
            x-cloak
        ></div>

        <div
            class="border-primary mb-4 break-words rounded-lg border bg-blue-50 p-4 text-sm text-primary-text"
            x-show="showOAuthProfile"
            x-cloak
        >
            <div class="flex items-center gap-3">
                <template x-if="settings.oauth_google_avatar_url && !oauthAvatarFailed">
                    <img
                        class="h-10 w-10 shrink-0 rounded-full object-cover"
                        x-bind:src="settings.oauth_google_avatar_url"
                        x-bind:alt="oauthProfileLabel"
                        referrerpolicy="no-referrer"
                        x-on:error="oauthAvatarFailed = true"
                    >
                </template>
                <template x-if="!settings.oauth_google_avatar_url || oauthAvatarFailed">
                    <div
                        class="bg-primary text-body flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                        x-text="oauthProfileInitial"
                    ></div>
                </template>
                <div class="min-w-0">
                    <p class="truncate font-semibold" x-text="oauthProfileLabel"></p>
                    <p
                        class="text-caption-text truncate text-xs"
                        x-show="settings.oauth_google_email"
                        x-text="settings.oauth_google_email"
                        x-cloak
                    ></p>
                    <p class="text-caption-text mt-1 text-xs">Авторизован для доступа к Google Таблицам</p>
                </div>
            </div>
            <div class="mt-3">
                <x-button.button
                    variant="secondary"
                    label="Выбрать другую учетную запись"
                    x-bind:disabled="oauthStarting || oauthApplying"
                    x-on:click="reauthorizeOAuthAccount()"
                />
            </div>
        </div>

        <x-form.form-field>
            <x-form.form-label
                class="self-baseline"
                tooltip="Ползунок синхронизации Google Таблиц"
            >
                Синхронизация
            </x-form.form-label>
            <div class="flex w-[305px] items-center gap-4">
                <div :class="{ 'yd-direct-error-toggle': hasIntegrationError && settings.is_enabled }">
                    <x-form.toggle-switch x-model="settings.is_enabled" x-bind:disabled="!canEdit || !platformConfigured"></x-form.toggle-switch>
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
                tooltip="Вставьте полный URL-адрес Google Таблицы, мы самостоятельно определим её ID"
            >
                ID Google таблицы
            </x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text
                    x-model="settings.document_id"
                    x-on:paste="onDocumentIdPaste()"
                    x-on:blur="onDocumentIdBlur()"
                ></x-form.input-text>
            </div>
        </x-form.form-field>
    </x-form.form>

    <x-project-form.integration-modal-footer class="mt-5" />
</div>
