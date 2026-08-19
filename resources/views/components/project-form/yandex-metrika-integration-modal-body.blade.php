@props([
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
    'canEdit' => true,
])

@php
    use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
    use App\Enums\AttributionModel;
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
    use Src\Domain\ValueObjects\ProjectType;

    $savedSettings = $projectIntegration->settings ?? [];
    $getSetting = function (string $key, mixed $default = '') use ($savedSettings) {
        if (array_key_exists($key, $savedSettings)) {
            return $savedSettings[$key];
        }

        $camel = Str::camel($key);
        if (array_key_exists($camel, $savedSettings)) {
            return $savedSettings[$camel];
        }

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

    $savedFilters = $getSetting('filters', []);
    if (! is_array($savedFilters)) {
        $savedFilters = [];
    }

    $savedReports = $getSetting('reports', []);
    if (! is_array($savedReports)) {
        $savedReports = [];
    }

    $attributionOptions = AttributionModel::options();
    $dataModeOptions = [
        ['value' => 'without_robots', 'label' => 'Без роботов'],
        ['value' => 'with_robots', 'label' => 'С роботами'],
    ];
    $exclusiveGoalReportKeys = [
        'goals_search_engines',
        'goals_utm',
        'goals_conversions',
        'goals_direct_summary',
    ];
    $seoOnlyVisitReportKeys = [
        'visits_search_engines',
        'visits_search_queries',
    ];
    $seoOnlyGoalReportKeys = [
        'goals_search_engines',
    ];
    $exclusiveGoalSourceTooltip = 'Может быть выбран только один источник достижения целей';
    $seoOnlyVisitTooltip = 'Доступен только для клиенто-проектов с типом SEO-продвижение';
    $seoOnlyGoalTooltip = 'Доступен только для клиенто-проектов с типом SEO-продвижение';
    $contextOnlyGoalReportKeys = [];
    $contextOnlyTooltip = 'Доступен только для клиенто-проектов с типом Контекстная реклама';
    $goalsMetricOptions = [
        ['value' => YandexMetrikaIntegrationSettingsData::GOALS_METRIC_TARGET_VISITS, 'label' => 'Целевые визиты'],
        ['value' => YandexMetrikaIntegrationSettingsData::GOALS_METRIC_GOAL_REACHES, 'label' => 'Достижения цели'],
    ];
    $reportOptions = [
        ['key' => 'goals_search_engines', 'label' => 'Достижение целей из отчета Поисковые системы', 'exclusive_goal_source' => true, 'seo_only' => true],
        ['key' => 'goals_utm', 'label' => 'Достижение целей из отчета UTM-метки', 'exclusive_goal_source' => true],
        ['key' => 'goals_conversions', 'label' => 'Достижение целей из отчета Конверсии', 'exclusive_goal_source' => true],
        ['key' => 'goals_direct_summary', 'label' => 'Достижение целей из отчета Директ, сводка', 'exclusive_goal_source' => true],
        ['key' => 'visits_search_engines', 'label' => 'Переходы из отчета Поисковые системы', 'seo_only' => true],
        ['key' => 'visits_search_queries', 'label' => 'Переходы из отчета Поисковые запросы', 'seo_only' => true],
        ['key' => 'visits_geo', 'label' => 'Переходы из отчета География'],
    ];
    $primaryReportOption = $reportOptions[0];
    $otherReportOptions = array_slice($reportOptions, 1);
    $filterTypes = [
        'entry_page' => [
            'add' => 'Добавить фильтр по странице входа',
            'remove' => 'Удалить',
            'caption' => 'Страница входа',
            'placeholder' => 'Например, !*promo*',
        ],
        'last_search_phrase' => [
            'add' => 'Добавить фильтр по последней значимой поисковой фразе',
            'remove' => 'Удалить',
            'caption' => 'Последняя значимая поисковая фраза',
            'placeholder' => 'Например, !*кейс*',
        ],
        'geo' => [
            'add' => 'Добавить фильтр по географии',
            'remove' => 'Удалить',
            'caption' => 'География',
            'placeholder' => 'Например, !*Москва*',
        ],
    ];

    $filterTooltip = "Настройте условия фильтрации в отчетах: знак \"!\" перед условием означает отрицание (НЕ).\nУсловия И/ИЛИ проставляются автоматически.\nУтвердительные фильтры (без \"!\") идут с ИЛИ. Пример - Страница входа catalog ИЛИ store\nОтрицательные фильтры (с \"!\") идут с И. Каждое новое условие с новой строки";

    $savedGoals = $getSetting('goals', []);
    if (! is_array($savedGoals)) {
        $savedGoals = [];
    }

    $metrikaSettings = [
        'is_enabled' => $projectIntegration->isEnabled ?? false,
        'sync_enabled_at' => (string) $getSetting('sync_enabled_at', ''),
        'oauth_token' => (string) $getSetting('oauth_token', ''),
        'refresh_token' => (string) $getSetting('refresh_token', ''),
        'token_expires_at' => (string) $getSetting('token_expires_at', ''),
        'oauth_yandex_user_id' => (string) $getSetting('oauth_yandex_user_id', ''),
        'oauth_yandex_login' => (string) $getSetting('oauth_yandex_login', ''),
        'oauth_yandex_display_name' => (string) $getSetting('oauth_yandex_display_name', ''),
        'oauth_yandex_avatar_url' => (string) $getSetting('oauth_yandex_avatar_url', ''),
        'counter_id' => (string) $getSetting('counter_id', ''),
        'counter_domain' => (string) $getSetting('counter_domain', ''),
        'counter_time_zone' => (string) $getSetting('counter_time_zone', ''),
        'attribution_model' => (string) ($getSetting('attribution_model', YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL)
            ?: YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL),
        'data_mode' => (string) ($getSetting('data_mode', YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE)
            ?: YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE),
        'filters' => [
            'entry_page' => (string) ($savedFilters['entry_page'] ?? ''),
            'last_search_phrase' => (string) ($savedFilters['last_search_phrase'] ?? ''),
            'geo' => (string) ($savedFilters['geo'] ?? ''),
        ],
        'reports' => array_merge(
            YandexMetrikaIntegrationSettingsData::DEFAULT_REPORTS,
            array_map(fn ($value) => (bool) $value, $savedReports)
        ),
        'goals' => YandexMetrikaIntegrationSettingsData::normalizeGoalIds($savedGoals),
        'goals_metric' => YandexMetrikaIntegrationSettingsData::normalizeGoalsMetric($getSetting(
            'goals_metric',
            YandexMetrikaIntegrationSettingsData::DEFAULT_GOALS_METRIC
        )),
    ];
@endphp

<div
    wire:ignore
    class="flex h-full w-fit min-w-0 flex-col"
    x-data="{
        canEdit: @js($canEdit),
        platformConfigured: {{ Js::from($platformConfigured) }},
        settings: {{ Js::from($metrikaSettings) }},
        attributionOptions: {{ Js::from($attributionOptions) }},
        dataModeOptions: {{ Js::from($dataModeOptions) }},
        goalsMetricOptions: {{ Js::from($goalsMetricOptions) }},
        counterOptions: [],
        counterSelectOpen: false,
        counterSearchQuery: '',
        attributionSelectOpen: false,
        dataModeSelectOpen: false,
        goalsMetricSelectOpen: false,
        countersLoading: false,
        countersError: null,
        goalOptions: [],
        goalsLoading: false,
        goalsError: null,
        testPanelOpen: false,
        testDate: '',
        testCount: null,
        testLoading: false,
        testError: null,
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
        oauthAvatarFailed: false,
        onOAuthAppliedHandler: null,
        onOAuthMessageHandler: null,
        integrationId: {{ (int) $projectIntegration->integration->id }},
        shownFilters: {
            entry_page: {{ Js::from($metrikaSettings['filters']['entry_page'] !== '') }},
            last_search_phrase: {{ Js::from($metrikaSettings['filters']['last_search_phrase'] !== '') }},
            geo: {{ Js::from($metrikaSettings['filters']['geo'] !== '') }},
        },
        exclusiveGoalReportKeys: {{ Js::from($exclusiveGoalReportKeys) }},
        seoOnlyVisitReportKeys: {{ Js::from($seoOnlyVisitReportKeys) }},
        seoOnlyGoalReportKeys: {{ Js::from($seoOnlyGoalReportKeys) }},
        contextOnlyGoalReportKeys: {{ Js::from($contextOnlyGoalReportKeys) }},
        seoPromotionType: {{ Js::from(ProjectType::SEO_PROMOTION->value) }},
        contextAdType: {{ Js::from(ProjectType::CONTEXT_AD->value) }},
        exclusiveGoalSourceTooltip: {{ Js::from($exclusiveGoalSourceTooltip) }},
        seoOnlyVisitTooltip: {{ Js::from($seoOnlyVisitTooltip) }},
        seoOnlyGoalTooltip: {{ Js::from($seoOnlyGoalTooltip) }},
        contextOnlyTooltip: {{ Js::from($contextOnlyTooltip) }},
        reportTooltipKey: null,

        currentProjectType() {
            return this.$wire?.clientProjectForm?.projectType ?? '';
        },

        isSeoPromotion() {
            return this.currentProjectType() === this.seoPromotionType;
        },

        isContextAd() {
            return this.currentProjectType() === this.contextAdType;
        },

        reportDisabledReason(key) {
            if (this.seoOnlyVisitReportKeys.includes(key) && !this.isSeoPromotion()) {
                return this.seoOnlyVisitTooltip;
            }

            if (this.seoOnlyGoalReportKeys.includes(key) && !this.isSeoPromotion()) {
                return this.seoOnlyGoalTooltip;
            }

            if (this.contextOnlyGoalReportKeys.includes(key) && !this.isContextAd()) {
                return this.contextOnlyTooltip;
            }

            if (
                this.exclusiveGoalReportKeys.includes(key)
                && this.exclusiveGoalReportKeys.some((other) => other !== key && this.settings.reports[other])
            ) {
                return this.exclusiveGoalSourceTooltip;
            }

            return '';
        },

        isReportDisabled(key) {
            return this.reportDisabledReason(key) !== '';
        },

        normalizeExclusiveGoalReports() {
            let kept = false;

            this.exclusiveGoalReportKeys.forEach((key) => {
                if (!this.settings.reports[key]) {
                    return;
                }

                if (kept) {
                    this.settings.reports[key] = false;
                    return;
                }

                kept = true;
            });
        },

        normalizeSeoOnlyVisitReports() {
            if (this.isSeoPromotion()) {
                return;
            }

            this.seoOnlyVisitReportKeys.forEach((key) => {
                this.settings.reports[key] = false;
            });
        },

        normalizeSeoOnlyGoalReports() {
            if (this.isSeoPromotion()) {
                return;
            }

            this.seoOnlyGoalReportKeys.forEach((key) => {
                this.settings.reports[key] = false;
            });
        },

        normalizeContextOnlyGoalReports() {
            if (this.isContextAd()) {
                return;
            }

            this.contextOnlyGoalReportKeys.forEach((key) => {
                this.settings.reports[key] = false;
            });
        },

        persistOAuthPending() {
            if (!this.oauthCacheDataId) {
                return;
            }

            localStorage.removeItem('casini_yandex_metrika_oauth_done');
            localStorage.setItem('casini_yandex_metrika_oauth', JSON.stringify({
                cacheDataId: this.oauthCacheDataId,
                ts: Date.now(),
            }));

            if (typeof window.__casiniStartYandexMetrikaOAuthPolling === 'function') {
                window.__casiniStartYandexMetrikaOAuthPolling();
            }
        },

        clearOAuthPending() {
            localStorage.removeItem('casini_yandex_metrika_oauth');
            localStorage.removeItem('casini_yandex_metrika_oauth_done');
        },

        init() {
            this.normalizeExclusiveGoalReports();
            this.normalizeSeoOnlyVisitReports();
            this.normalizeSeoOnlyGoalReports();
            this.normalizeContextOnlyGoalReports();

            if (this.$wire && typeof this.$wire.$watch === 'function') {
                this.$wire.$watch('clientProjectForm.projectType', () => {
                    this.normalizeSeoOnlyVisitReports();
                    this.normalizeSeoOnlyGoalReports();
                    this.normalizeContextOnlyGoalReports();
                });
            }

            this.$watch('settings.reports.goals_search_engines', (enabled) => {
                if (enabled) {
                    this.loadGoals();
                    return;
                }

                this.testPanelOpen = false;
                this.testDate = '';
                this.testCount = null;
                this.testError = null;
            });

            this.$watch('testDate', (value) => {
                if (this.testPanelOpen && value) {
                    this.runTest();
                }
            });

            this.$watch('settings.counter_id', () => {
                if (this.settings.reports.goals_search_engines) {
                    this.loadGoals();
                }
            });

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
            window.addEventListener('yandex-metrika-oauth-applied', this.onOAuthAppliedHandler);

            this.onOAuthMessageHandler = (event) => this.onOAuthMessage(event);
            window.addEventListener('message', this.onOAuthMessageHandler);

            if (typeof BroadcastChannel !== 'undefined') {
                this.oauthBroadcast = new BroadcastChannel('yandex-metrika-oauth');
                this.oauthBroadcast.onmessage = (event) => this.handleOAuthPayload(event.data);
            }

            if (this.settings.oauth_token) {
                this.loadCounters();

                if (this.needsOAuthProfileRefresh) {
                    this.loadOAuthProfile();
                }
            }
        },

        destroy() {
            this.stopOAuthWatchdog();

            if (this.onOAuthAppliedHandler) {
                window.removeEventListener('yandex-metrika-oauth-applied', this.onOAuthAppliedHandler);
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

            const hasCounter = this.settings.counter_id !== ''
                && this.counterOptions.some(o => String(o.value) === String(this.settings.counter_id));

            if (!hasCounter) {
                return false;
            }

            if (this.settings.is_enabled && !this.settings.oauth_token) {
                return false;
            }

            if (this.settings.reports?.goals_search_engines) {
                if (!Array.isArray(this.settings.goals) || this.settings.goals.length === 0) {
                    return false;
                }
            }

            return Object.values(this.settings.reports || {}).some(Boolean);
        },

        get syncEnabledLabel() {
            if (!this.settings.sync_enabled_at) {
                return '';
            }

            const [y, m, d] = this.settings.sync_enabled_at.split('-');

            return `включена: ${d}.${m}.${y}`;
        },

        get integrationError() {
            return this.oauthError || this.countersError || null;
        },

        get hasIntegrationError() {
            return this.integrationError !== null && this.integrationError !== '';
        },

        get oauthProfileLabel() {
            return this.settings.oauth_yandex_display_name
                || this.settings.oauth_yandex_login
                || '';
        },

        get oauthProfileLoginLabel() {
            const login = this.settings.oauth_yandex_login;
            const displayName = this.settings.oauth_yandex_display_name;

            if (!login || !displayName) {
                return '';
            }

            if (displayName.toLowerCase() === login.toLowerCase()) {
                return '';
            }

            return login.startsWith('@') ? login : '@' + login;
        },

        get oauthProfileInitial() {
            const source = this.oauthProfileLabel || this.settings.oauth_yandex_login || '?';

            return source.charAt(0).toUpperCase();
        },

        get showOAuthProfile() {
            return Boolean(this.settings.oauth_token && this.oauthProfileLabel);
        },

        get needsOAuthProfileRefresh() {
            if (!this.settings.oauth_token) {
                return false;
            }

            if (!this.settings.oauth_yandex_login) {
                return true;
            }

            const avatarUrl = this.settings.oauth_yandex_avatar_url || '';

            if (!avatarUrl || avatarUrl.includes('%2F')) {
                return true;
            }

            return false;
        },

        get counterSelectLabel() {
            if (this.countersLoading) {
                return 'Загрузка...';
            }

            if (!this.settings.oauth_token) {
                return 'Сначала включите синхронизацию';
            }

            if (this.settings.counter_id) {
                const option = this.counterOptions.find(
                    o => String(o.value) === String(this.settings.counter_id)
                );

                if (option) {
                    return option.label;
                }
            }

            if (this.counterOptions.length === 0) {
                return 'Нет доступных счётчиков';
            }

            return 'Выберите счётчик';
        },

        get counterSelectDisabled() {
            return !this.settings.oauth_token || this.countersLoading || this.counterOptions.length === 0;
        },

        get filteredCounterOptions() {
            const q = this.counterSearchQuery.trim().toLowerCase();
            if (!q) {
                return this.counterOptions;
            }
            return this.counterOptions.filter(o =>
                String(o.label).toLowerCase().includes(q)
                || String(o.value).toLowerCase().includes(q)
                || String(o.domain || '').toLowerCase().includes(q)
            );
        },

        attributionSelectLabel() {
            const option = this.attributionOptions.find(
                o => String(o.value) === String(this.settings.attribution_model)
            );

            return option ? option.label : 'Выберите атрибуцию';
        },

        dataModeSelectLabel() {
            const option = this.dataModeOptions.find(
                o => String(o.value) === String(this.settings.data_mode)
            );

            return option ? option.label : 'Выберите значение';
        },

        goalsMetricSelectLabel() {
            const option = this.goalsMetricOptions.find(
                o => String(o.value) === String(this.settings.goals_metric)
            );

            return option ? option.label : 'Выберите значение';
        },

        toggleCounterSelect() {
            if (this.counterSelectDisabled) {
                return;
            }

            this.counterSelectOpen = !this.counterSelectOpen;
            this.counterSearchQuery = '';

            if (this.counterSelectOpen) {
                this.$nextTick(() => {
                    this.$refs.counterSearchInput?.focus();
                });
            }
        },

        selectCounter(option) {
            this.settings.counter_id = String(option.value);
            this.settings.counter_domain = option.domain || '';
            this.settings.counter_time_zone = option.time_zone_name || '';
            this.counterSelectOpen = false;
            this.counterSearchQuery = '';
        },

        selectAttribution(value) {
            this.settings.attribution_model = String(value);
            this.attributionSelectOpen = false;
        },

        selectDataMode(value) {
            this.settings.data_mode = String(value);
            this.dataModeSelectOpen = false;
        },

        selectGoalsMetric(value) {
            this.settings.goals_metric = String(value);
            this.goalsMetricSelectOpen = false;
        },

        isGoalSelected(id) {
            return (this.settings.goals || []).some(goalId => Number(goalId) === Number(id));
        },

        toggleGoal(id) {
            const value = Number(id);
            if (!Array.isArray(this.settings.goals)) {
                this.settings.goals = [];
            }

            const index = this.settings.goals.findIndex(goalId => Number(goalId) === value);
            if (index === -1) {
                this.settings.goals.push(value);
            } else {
                this.settings.goals.splice(index, 1);
            }
        },

        showFilter(key) {
            this.shownFilters[key] = true;
        },

        hideFilter(key) {
            this.shownFilters[key] = false;
            this.settings.filters[key] = '';
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
                    this.failOAuthStart('Не удалось начать авторизацию Яндекс Метрики');
                }
            }, 3000);
        },

        onVisibilityChange() {
            if (document.visibilityState !== 'visible') {
                return;
            }

            if (this.oauthCacheDataId && !this.settings.oauth_token && !this.oauthApplying) {
                if (typeof window.__casiniTryYandexMetrikaOAuth === 'function') {
                    window.__casiniTryYandexMetrikaOAuth(this.oauthCacheDataId);
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

            this.applyOAuthSettings(settings, detail.counters ?? [], detail.countersError ?? null, true);
        },

        handleOAuthPayload(data) {
            if (!data) {
                return;
            }

            if (data.type === 'yandex-metrika-oauth-error') {
                this.failOAuthStart(data.error || 'Не удалось завершить авторизацию Яндекс Метрики');
                return;
            }

            if (data.type !== 'yandex-metrika-oauth') {
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
                this.oauthPopupWindowName = 'yandex-metrika-oauth-' + (this.oauthCacheDataId || 'session') + '-' + Date.now();

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
                console.error('Yandex Metrika OAuth popup navigate failed', e);
                this.oauthNavigateCompleted = false;
                this.failOAuthStart('Не удалось начать авторизацию Яндекс Метрики');
            }
        },

        async reauthorizeOAuthAccount() {
            if (this.oauthStarting || this.oauthApplying || this.oauthPopupOpened || this.oauthNavigateCompleted) {
                return;
            }

            this.settings.oauth_token = '';
            this.settings.refresh_token = '';
            this.settings.token_expires_at = '';
            this.settings.oauth_yandex_user_id = '';
            this.settings.oauth_yandex_login = '';
            this.settings.oauth_yandex_display_name = '';
            this.settings.oauth_yandex_avatar_url = '';
            this.settings.counter_id = '';
            this.settings.counter_domain = '';
            this.settings.counter_time_zone = '';
            this.oauthAvatarFailed = false;
            this.counterOptions = [];
            this.countersError = null;
            this.oauthError = null;

            await this.startOAuth();
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
            this.settings.counter_id = '';
            this.settings.counter_domain = '';
            this.settings.counter_time_zone = '';
            this.settings.refresh_token = '';
            this.settings.token_expires_at = '';
            this.settings.oauth_yandex_user_id = '';
            this.settings.oauth_yandex_login = '';
            this.settings.oauth_yandex_display_name = '';
            this.settings.oauth_yandex_avatar_url = '';
            this.oauthAvatarFailed = false;
            this.counterOptions = [];
            this.countersError = null;
            this.stopOAuthWatchdog();

            if (!this.platformConfigured) {
                this.failOAuthStart('Интеграция Яндекс Метрики не настроена на сервере. Обратитесь к администратору.');
                return;
            }

            const useRedirect = this.shouldUseRedirectOAuth();
            this.startOAuthWatchdog();

            try {
                const result = await $wire.prepareYandexMetrikaOAuth(! useRedirect);

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
                    this.failOAuthStart('Не удалось начать авторизацию Яндекс Метрики');
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
                console.error('Yandex Metrika OAuth start failed', e);

                if (!this.oauthNavigateCompleted) {
                    this.failOAuthStart('Не удалось начать авторизацию Яндекс Метрики');
                }
            }
        },

        async applyOAuthSettings(oauthSettings, counters = null, countersError = null, fromServer = false) {
            if (this.oauthApplying) {
                return;
            }

            if (this.settings.oauth_token && oauthSettings?.oauth_token === this.settings.oauth_token) {
                if (oauthSettings.oauth_yandex_login) {
                    this.settings.oauth_yandex_user_id = oauthSettings.oauth_yandex_user_id || '';
                    this.settings.oauth_yandex_login = oauthSettings.oauth_yandex_login || '';
                    this.settings.oauth_yandex_display_name = oauthSettings.oauth_yandex_display_name || '';
                    this.settings.oauth_yandex_avatar_url = oauthSettings.oauth_yandex_avatar_url || '';
                    this.oauthAvatarFailed = false;
                }

                if (Array.isArray(counters) && counters.length > 0) {
                    this.counterOptions = counters;
                    this.countersError = countersError;
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
            this.settings.oauth_yandex_user_id = oauthSettings.oauth_yandex_user_id || '';
            this.settings.oauth_yandex_login = oauthSettings.oauth_yandex_login || '';
            this.settings.oauth_yandex_display_name = oauthSettings.oauth_yandex_display_name || '';
            this.settings.oauth_yandex_avatar_url = oauthSettings.oauth_yandex_avatar_url || '';
            this.oauthAvatarFailed = false;
            this.settings.counter_id = '';
            this.settings.counter_domain = '';
            this.settings.counter_time_zone = '';
            this.settings.is_enabled = true;
            this.settings.sync_enabled_at = oauthSettings.sync_enabled_at || this.todayIso();
            this.oauthError = null;

            if (this.oauthPopup && !this.oauthPopup.closed) {
                this.oauthPopup.close();
            }

            if (!fromServer) {
                try {
                    await $wire.applyYandexMetrikaOAuthTokens({
                        oauth_token: this.settings.oauth_token,
                        refresh_token: this.settings.refresh_token,
                        token_expires_at: this.settings.token_expires_at,
                        oauth_yandex_user_id: this.settings.oauth_yandex_user_id,
                        oauth_yandex_login: this.settings.oauth_yandex_login,
                        oauth_yandex_display_name: this.settings.oauth_yandex_display_name,
                        oauth_yandex_avatar_url: this.settings.oauth_yandex_avatar_url,
                    });
                } catch (e) {
                    // Alpine state already updated; Livewire sync is best-effort
                }
            }

            if (Array.isArray(counters) && counters.length > 0) {
                this.counterOptions = counters;
                this.countersError = countersError;
                this.oauthError = null;
            } else if (countersError) {
                this.counterOptions = [];
                this.countersError = countersError;
            } else {
                await this.loadCounters();
            }

            this.oauthApplying = false;
        },

        async loadOAuthProfile() {
            if (!this.settings.oauth_token || !this.needsOAuthProfileRefresh) {
                return;
            }

            try {
                const result = await $wire.loadYandexMetrikaOAuthProfile(this.settings.oauth_token);

                if (result?.profile) {
                    this.settings.oauth_yandex_user_id = result.profile.oauth_yandex_user_id || '';
                    this.settings.oauth_yandex_login = result.profile.oauth_yandex_login || '';
                    this.settings.oauth_yandex_display_name = result.profile.oauth_yandex_display_name || '';
                    this.settings.oauth_yandex_avatar_url = result.profile.oauth_yandex_avatar_url || '';
                    this.oauthAvatarFailed = false;
                }
            } catch (e) {
                // Профиль — подсказка в UI; без него модалка остаётся рабочей
            }
        },

        async loadCounters() {
            if (!this.settings.oauth_token) {
                this.counterOptions = [];
                return;
            }

            this.countersLoading = true;
            this.countersError = null;

            try {
                const result = await $wire.loadYandexMetrikaCounters(this.settings.oauth_token);

                if (result.error) {
                    this.countersError = result.error;
                    this.counterOptions = [];
                } else {
                    this.countersError = null;
                    this.oauthError = null;
                    this.counterOptions = result.counters ?? [];

                    if (
                        this.settings.counter_id
                        && !this.counterOptions.some(o => String(o.value) === String(this.settings.counter_id))
                    ) {
                        this.settings.counter_id = '';
                        this.settings.counter_domain = '';
                        this.settings.counter_time_zone = '';
                    } else if (this.settings.counter_id) {
                        const selected = this.counterOptions.find(
                            o => String(o.value) === String(this.settings.counter_id)
                        );
                        if (selected?.time_zone_name) {
                            this.settings.counter_time_zone = selected.time_zone_name;
                        }
                    }
                }
            } catch (e) {
                this.countersError = 'Не удалось загрузить счётчики Яндекс Метрики';
                this.counterOptions = [];
            }

            this.countersLoading = false;

            if (this.settings.reports.goals_search_engines) {
                this.loadGoals();
            }
        },

        async loadGoals() {
            if (!this.settings.oauth_token || !this.settings.counter_id) {
                this.goalOptions = [];
                return;
            }

            this.goalsLoading = true;
            this.goalsError = null;

            try {
                const result = await $wire.loadYandexMetrikaGoals(
                    this.settings.oauth_token,
                    Number(this.settings.counter_id),
                    this.settings.oauth_yandex_login || ''
                );

                if (result.error) {
                    this.goalsError = result.error;
                    this.goalOptions = [];
                } else {
                    this.goalOptions = result.goals ?? [];
                    const validIds = this.goalOptions.map(goal => Number(goal.id));
                    this.settings.goals = (this.settings.goals || [])
                        .map(Number)
                        .filter(id => validIds.includes(id));
                }
            } catch (e) {
                this.goalsError = 'Не удалось загрузить цели Яндекс Метрики';
                this.goalOptions = [];
            }

            this.goalsLoading = false;
        },

        toggleTestPanel() {
            this.testPanelOpen = !this.testPanelOpen;

            if (!this.testPanelOpen) {
                return;
            }

            if (this.testDate) {
                this.runTest();
            }

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.$refs.metrikaTestPanel?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'end',
                        inline: 'nearest',
                    });
                });
            });
        },

        async runTest() {
            if (!this.testDate) {
                this.testError = 'Укажите дату';
                return;
            }

            if (!Array.isArray(this.settings.goals) || this.settings.goals.length === 0) {
                this.testError = 'Выберите хотя бы одну цель';
                return;
            }

            this.testLoading = true;
            this.testError = null;
            this.testCount = null;

            const result = await $wire.testYandexMetrikaGoalsSearchEnginesIntegration(this.settings, this.testDate);

            if (result.error) {
                this.testError = result.error;
            } else {
                this.testCount = result.count;
            }

            this.testLoading = false;
        },

        save() {
            if (!this.canEdit || !this.canSave) {
                return;
            }

            const payload = { ...this.settings };
            payload.goals = (this.settings.goals || []).map(Number).filter(id => id > 0);
            payload.filters = {
                entry_page: this.shownFilters.entry_page && this.settings.filters.entry_page.trim() !== ''
                    ? this.settings.filters.entry_page
                    : null,
                last_search_phrase: this.shownFilters.last_search_phrase && this.settings.filters.last_search_phrase.trim() !== ''
                    ? this.settings.filters.last_search_phrase
                    : null,
                geo: this.shownFilters.geo && this.settings.filters.geo.trim() !== ''
                    ? this.settings.filters.geo
                    : null,
            };

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
    <x-panel.scroll-panel style="max-height: 500px">
    <x-form.form class="lg:min-w-[580px]">
        <div class="border-primary mb-4 break-words rounded-lg border bg-blue-50 p-4 text-sm text-primary-text">
            Чтобы цифры в Касини совпадали с интерфейсом Яндекс Метрики, проверьте, чтобы в
            <a
                class="text-primary underline"
                href="{{ route('system-settings.agency.default') }}"
            >настройках агентства</a>
            поле «Основной часовой пояс агентства» совпадало с часовым поясом счётчика в Яндекс Метрике.
        </div>

        @unless ($platformConfigured)
            <p class="text-warning-red mb-4 text-sm">
                Интеграция Яндекс Метрики не настроена на сервере. Обратитесь к администратору.
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
                <template x-if="settings.oauth_yandex_avatar_url && !oauthAvatarFailed">
                    <img
                        class="h-10 w-10 shrink-0 rounded-full object-cover"
                        x-bind:src="settings.oauth_yandex_avatar_url"
                        x-bind:alt="oauthProfileLabel"
                        referrerpolicy="no-referrer"
                        x-on:error="oauthAvatarFailed = true"
                    >
                </template>
                <template x-if="!settings.oauth_yandex_avatar_url || oauthAvatarFailed">
                    <div
                        class="bg-primary text-body flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                        x-text="oauthProfileInitial"
                    ></div>
                </template>
                <div class="min-w-0">
                    <p class="truncate font-semibold" x-text="oauthProfileLabel"></p>
                    <p
                        class="text-caption-text truncate text-xs"
                        x-show="oauthProfileLoginLabel"
                        x-text="oauthProfileLoginLabel"
                        x-cloak
                    ></p>
                    <p class="text-caption-text mt-1 text-xs">Авторизован для доступа к API Метрики</p>
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
                tooltip="Ползунок синхронизации аккаунта Яндекс Метрики"
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
                tooltip="Счётчик Яндекс Метрики, из которого будут запрашиваться данные"
            >
                Номер счетчика
            </x-form.form-label>
            <div class="w-[305px]">
                <div class="text-input-text relative select-none">
                    <div class="group" x-ref="counterSelectButton">
                        <div
                            class="border-input-border flex min-h-[42px] w-full items-center rounded-[5px] border pe-10 ps-4"
                            x-ref="counterSelectTrigger"
                            x-on:click="toggleCounterSelect()"
                            x-bind:class="{
                                'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': counterSelectOpen,
                                'rounded-[5px]': !counterSelectOpen,
                                'yd-direct-error-login': hasIntegrationError,
                                'bg-secondary': counterSelectDisabled && !hasIntegrationError,
                                'opacity-70': !countersLoading && !settings.oauth_token
                            }"
                        >
                            <input
                                type="text"
                                class="w-full border-0 bg-transparent p-0 outline-none placeholder:text-gray-400"
                                placeholder="Поиск по домену или номеру"
                                x-ref="counterSearchInput"
                                x-model="counterSearchQuery"
                                x-show="counterSelectOpen"
                                x-on:click.stop
                                x-on:keydown.escape="counterSelectOpen = false; counterSearchQuery = ''"
                            />
                            <span
                                class="overflow-hidden"
                                x-text="counterSelectLabel"
                                x-show="!counterSelectOpen"
                                x-bind:class="{
                                    'opacity-50': settings.oauth_token && !settings.counter_id && counterOptions.length > 0,
                                    'text-gray-400 italic': !settings.oauth_token || counterOptions.length === 0
                                }"
                            ></span>
                        </div>

                        <template x-if="!counterSelectDisabled && counterOptions.length > 0">
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                                <x-icons.arrow
                                    class="transition-transform duration-300"
                                    x-bind:class="{
                                        'rotate-180 group-hover:text-white': counterSelectOpen,
                                    }"
                                />
                            </span>
                        </template>
                    </div>

                    <div
                        class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0"
                        x-cloak
                        x-show="counterSelectOpen && counterOptions.length > 0"
                        x-anchor.no-style="$refs.counterSelectButton"
                        x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
                        x-on:click.outside="counterSelectOpen = false; counterSearchQuery = ''"
                    >
                        <div
                            class="flex min-h-[42px] items-center bg-white px-4 text-sm text-gray-400 italic"
                            x-show="filteredCounterOptions.length === 0"
                        >Ничего не найдено</div>
                        <template x-for="option in filteredCounterOptions" :key="option.value">
                            <div
                                class="hover:bg-primary flex min-h-[42px] cursor-pointer items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                                x-on:click="selectCounter(option)"
                                x-text="option.label"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label class="self-baseline">Атрибуция</x-form.form-label>
            <div class="w-[305px]">
                <div class="text-input-text relative select-none">
                    <div class="group" x-ref="attributionSelectButton">
                        <div
                            class="border-input-border flex min-h-[42px] w-full cursor-pointer items-center rounded-[5px] border pe-10 ps-4"
                            x-on:click="attributionSelectOpen = !attributionSelectOpen"
                            x-bind:class="{
                                'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': attributionSelectOpen,
                                'rounded-[5px]': !attributionSelectOpen,
                            }"
                        >
                            <span class="overflow-hidden" x-text="attributionSelectLabel()"></span>
                        </div>
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                            <x-icons.arrow
                                class="transition-transform duration-300"
                                x-bind:class="{ 'rotate-180 group-hover:text-white': attributionSelectOpen }"
                            />
                        </span>
                    </div>
                    <div
                        class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0"
                        x-cloak
                        x-show="attributionSelectOpen"
                        x-anchor.no-style="$refs.attributionSelectButton"
                        x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
                        x-on:click.outside="attributionSelectOpen = false"
                    >
                        <template x-for="option in attributionOptions" :key="option.value">
                            <div
                                class="hover:bg-primary flex min-h-[42px] cursor-pointer items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                                x-on:click="selectAttribution(option.value)"
                                x-text="option.label"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label class="self-baseline">Данные</x-form.form-label>
            <div class="w-[305px]">
                <div class="text-input-text relative select-none">
                    <div class="group" x-ref="dataModeSelectButton">
                        <div
                            class="border-input-border flex min-h-[42px] w-full cursor-pointer items-center rounded-[5px] border pe-10 ps-4"
                            x-on:click="dataModeSelectOpen = !dataModeSelectOpen"
                            x-bind:class="{
                                'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': dataModeSelectOpen,
                                'rounded-[5px]': !dataModeSelectOpen,
                            }"
                        >
                            <span class="overflow-hidden" x-text="dataModeSelectLabel()"></span>
                        </div>
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                            <x-icons.arrow
                                class="transition-transform duration-300"
                                x-bind:class="{ 'rotate-180 group-hover:text-white': dataModeSelectOpen }"
                            />
                        </span>
                    </div>
                    <div
                        class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0"
                        x-cloak
                        x-show="dataModeSelectOpen"
                        x-anchor.no-style="$refs.dataModeSelectButton"
                        x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
                        x-on:click.outside="dataModeSelectOpen = false"
                    >
                        <template x-for="option in dataModeOptions" :key="option.value">
                            <div
                                class="hover:bg-primary flex min-h-[42px] cursor-pointer items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                                x-on:click="selectDataMode(option.value)"
                                x-text="option.label"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <div class="flex gap-3">
                <label class="max-w-[250px] self-baseline text-sm">Условия фильтрации</label>
                <x-overlay.tooltip>
                    <span class="whitespace-pre-line">{{ $filterTooltip }}</span>
                </x-overlay.tooltip>
            </div>
            <div class="flex w-[305px] flex-col gap-4">
                @foreach ($filterTypes as $filterKey => $filterMeta)
                    <div>
                        <template x-if="!shownFilters.{{ $filterKey }}">
                            <x-button.button
                                class="self-start"
                                type="button"
                                variant="action"
                                wrap
                                x-on:click="showFilter('{{ $filterKey }}')"
                            >
                                <x-slot:label>
                                    {{ $filterMeta['add'] }}
                                </x-slot:label>
                            </x-button.button>
                        </template>
                        <div class="flex flex-col gap-2" x-show="shownFilters.{{ $filterKey }}" x-cloak>
                            <span class="text-secondary-text text-sm italic">{{ $filterMeta['caption'] }}</span>
                            <x-form.textarea
                                class="min-h-[84px] w-full rounded-[5px] border border-input-border px-3 py-2"
                                rows="3"
                                placeholder="{{ $filterMeta['placeholder'] }}"
                                x-model="settings.filters.{{ $filterKey }}"
                            />
                            <x-button.button
                                type="button"
                                icon="icons.delete"
                                class="w-full"
                                label="{{ $filterMeta['remove'] }}"
                                x-on:click="hideFilter('{{ $filterKey }}')"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-form.form-field>

        @php
            $reportKey = $primaryReportOption['key'];
        @endphp
        <x-form.form-field>
            <x-form.form-label class="self-baseline" required>
                Какие отчеты нужно подтянуть из Яндекс Метрики?
            </x-form.form-label>
            <div class="w-[305px]">
                <label
                    class="flex items-center justify-between gap-2 text-sm"
                    x-ref="goalReport_{{ $reportKey }}"
                    x-bind:class="isReportDisabled('{{ $reportKey }}') && 'cursor-not-allowed text-secondary-text'"
                    x-on:mouseenter="reportTooltipKey = isReportDisabled('{{ $reportKey }}') ? '{{ $reportKey }}' : null"
                    x-on:mouseleave="reportTooltipKey = null"
                >
                    <span>{{ $primaryReportOption['label'] }}</span>
                    <x-form.checkbox
                        x-model="settings.reports.{{ $reportKey }}"
                        x-bind:disabled="isReportDisabled('{{ $reportKey }}')"
                    />
                    <template x-teleport="body">
                        <div
                            class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                            style="z-index: 1000"
                            x-show="reportTooltipKey === '{{ $reportKey }}'"
                            x-cloak
                            x-anchor.bottom="$refs.goalReport_{{ $reportKey }}"
                            x-text="reportDisabledReason('{{ $reportKey }}')"
                        ></div>
                    </template>
                </label>
            </div>
        </x-form.form-field>

        <div
            class="flex flex-col gap-2"
            x-show="settings.reports.goals_search_engines"
            x-cloak
        >
            <x-form.form-label required>
                Выберите цели, по которым хотите получать статистику
            </x-form.form-label>
            <div>
                <p class="text-secondary-text text-sm" x-show="goalsLoading" x-cloak>Загрузка целей…</p>
                <p class="text-warning-red text-sm" x-show="goalsError" x-text="goalsError" x-cloak></p>
                <p
                    class="text-secondary-text text-sm"
                    x-show="!goalsLoading && !goalsError && goalOptions.length === 0"
                    x-cloak
                >У счётчика нет целей</p>
                <div
                    class="border-input-border max-h-40 overflow-y-auto rounded-[5px] border px-3"
                    x-show="!goalsLoading && !goalsError && goalOptions.length > 0"
                    x-cloak
                >
                    <template x-for="goal in goalOptions" :key="goal.id">
                        <label
                            class="flex min-h-[40px] cursor-pointer items-center gap-2 text-sm"
                            x-on:click.prevent="toggleGoal(goal.id)"
                        >
                            <x-form.checkbox
                                x-bind:checked="isGoalSelected(goal.id)"
                            />
                            <span class="min-w-0">
                                <span x-text="goal.name"></span>
                                <span class="text-secondary-text" x-text="' (№' + goal.id + ')'"></span>
                            </span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        <x-form.form-field x-show="settings.reports.goals_search_engines" x-cloak>
            <x-form.form-label class="self-baseline">
                По какому параметру рассчитываем достижение целей?
            </x-form.form-label>
            <div class="flex w-[305px] flex-col gap-3">
                <div class="text-input-text relative select-none">
                    <div class="group" x-ref="goalsMetricSelectButton">
                        <div
                            class="border-input-border flex min-h-[42px] w-full cursor-pointer items-center rounded-[5px] border pe-10 ps-4"
                            x-on:click="goalsMetricSelectOpen = !goalsMetricSelectOpen"
                            x-bind:class="{
                                'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': goalsMetricSelectOpen,
                                'rounded-[5px]': !goalsMetricSelectOpen,
                            }"
                        >
                            <span class="overflow-hidden" x-text="goalsMetricSelectLabel()"></span>
                        </div>
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                            <x-icons.arrow
                                class="transition-transform duration-300"
                                x-bind:class="{ 'rotate-180 group-hover:text-white': goalsMetricSelectOpen }"
                            />
                        </span>
                    </div>
                    <div
                        class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0"
                        x-cloak
                        x-show="goalsMetricSelectOpen"
                        x-anchor.no-style="$refs.goalsMetricSelectButton"
                        x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
                        x-on:click.outside="goalsMetricSelectOpen = false"
                    >
                        <template x-for="option in goalsMetricOptions" :key="option.value">
                            <div
                                class="hover:bg-primary flex min-h-[42px] cursor-pointer items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                                x-on:click="selectGoalsMetric(option.value)"
                                x-text="option.label"
                            ></div>
                        </template>
                    </div>
                </div>
                <x-button.button
                    class="self-start"
                    type="button"
                    variant="action"
                    wrap
                    label="Проверить работу интеграции"
                    x-on:click="toggleTestPanel()"
                    x-bind:aria-expanded="testPanelOpen"
                />
            </div>
        </x-form.form-field>

        <div
            class="flex flex-col gap-3"
            x-ref="metrikaTestPanel"
            x-show="settings.reports.goals_search_engines && testPanelOpen"
            x-cloak
        >
            <x-form.form-field>
                <x-form.form-label tooltip="Дата, за которую сверяем цифры с отчётом Поисковые системы в Яндекс Метрике">
                    Выберите дату
                </x-form.form-label>
                <div class="w-[305px]">
                    <x-form.date-picker
                        class="w-full"
                        placeholder="ДД.ММ.ГГ"
                        x-model="testDate"
                    ></x-form.date-picker>
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label tooltip="Сумма достижений выбранных целей за выбранную дату">
                    Количество достижений цели
                </x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text
                        disabled
                        x-bind:value="testCount === null ? '' : String(testCount)"
                    />
                    <p class="text-warning-red mt-1 text-xs" x-show="testError" x-text="testError" x-cloak></p>
                </div>
            </x-form.form-field>
        </div>

        <x-form.form-field>
            <x-form.form-label class="self-baseline">
            </x-form.form-label>
            <div class="flex w-[305px] flex-col gap-3">
                @foreach ($otherReportOptions as $reportOption)
                    @php
                        $reportKey = $reportOption['key'];
                    @endphp
                    <label
                        class="flex items-center justify-between gap-2 text-sm"
                        x-ref="goalReport_{{ $reportKey }}"
                        x-bind:class="isReportDisabled('{{ $reportKey }}') && 'cursor-not-allowed text-secondary-text'"
                        x-on:mouseenter="reportTooltipKey = isReportDisabled('{{ $reportKey }}') ? '{{ $reportKey }}' : null"
                        x-on:mouseleave="reportTooltipKey = null"
                    >
                        <span>{{ $reportOption['label'] }}</span>
                        <x-form.checkbox
                            x-model="settings.reports.{{ $reportKey }}"
                            x-bind:disabled="isReportDisabled('{{ $reportKey }}')"
                        />
                        <template x-teleport="body">
                            <div
                                class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                                style="z-index: 1000"
                                x-show="reportTooltipKey === '{{ $reportKey }}'"
                                x-cloak
                                x-anchor.bottom="$refs.goalReport_{{ $reportKey }}"
                                x-text="reportDisabledReason('{{ $reportKey }}')"
                            ></div>
                        </template>
                    </label>
                @endforeach
            </div>
        </x-form.form-field>
    </x-form.form>
    </x-panel.scroll-panel>

    <x-project-form.integration-modal-footer class="mt-4" />
</div>
