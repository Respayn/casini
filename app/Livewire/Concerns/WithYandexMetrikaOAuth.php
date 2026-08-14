<?php

namespace App\Livewire\Concerns;

use App\Data\ProjectForm\ProjectIntegrationData;
use App\Services\YandexMetrikaAuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

trait WithYandexMetrikaOAuth
{
    #[Computed]
    public function isYandexMetrikaOAuthConfigured(): bool
    {
        return filled(config('services.yandex_metrika.client_id'))
            && filled(config('services.yandex_metrika.client_secret'));
    }

    /**
     * @return array{url?: string, cache_data_id?: string, error?: string}
     */
    public function prepareYandexMetrikaOAuth(bool $popup = true): array
    {
        $this->ensureCanEdit();
        $this->ensureSelectedIntegration('yandex_metrika');

        if (! $this->isYandexMetrikaOAuthConfigured) {
            return [
                'error' => 'Интеграция Яндекс Метрики не настроена на сервере. Обратитесь к администратору.',
            ];
        }

        $cacheDataId = (string) Str::uuid();

        Cache::put(
            'integration_data_'.$cacheDataId,
            $this->buildOAuthCachePayload(),
            now()->addMinutes(15)
        );

        $url = route('yandex-metrika.auth', [
            'project_id' => $this->clientProjectForm->id,
            'cache_data_id' => $cacheDataId,
            'popup' => $popup ? 1 : 0,
        ]);

        return [
            'url' => $url,
            'cache_data_id' => $cacheDataId,
        ];
    }

    /**
     * @return array{settings?: array<string, mixed>, pending?: bool}
     */
    public function pullYandexMetrikaOAuthResult(string $cacheDataId): array
    {
        $this->ensureCanEdit();

        if (trim($cacheDataId) === '') {
            return ['pending' => true];
        }

        $settings = Cache::pull('yandex_metrika_oauth_result_'.$cacheDataId);

        if (! is_array($settings) || $settings === []) {
            return ['pending' => true];
        }

        return ['settings' => $settings];
    }

    /**
     * @return array{applied?: bool, pending?: bool}
     */
    public function finalizeYandexMetrikaOAuth(string $cacheDataId): array
    {
        $this->ensureCanEdit();

        $cacheDataId = trim($cacheDataId);

        if ($cacheDataId === '') {
            return ['pending' => true];
        }

        $settings = Cache::pull('yandex_metrika_oauth_result_'.$cacheDataId);

        if (! is_array($settings) || $settings === []) {
            return ['pending' => true];
        }

        $this->selectIntegration('yandex_metrika');
        $this->applyYandexMetrikaOAuthTokens($settings);
        $this->dispatch('modal-show', name: 'integration-settings-modal');

        return ['applied' => true];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function applyYandexMetrikaOAuthFromBroadcast(array $settings, ?int $integrationId = null): void
    {
        $this->ensureCanEdit();

        $this->selectIntegration('yandex_metrika');

        if ($integrationId !== null
            && $this->selectedIntegration?->integration?->id !== $integrationId) {
            return;
        }

        if ($settings === []) {
            return;
        }

        $this->applyYandexMetrikaOAuthTokens($settings);
        $this->dispatch('modal-show', name: 'integration-settings-modal');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    #[On('yandex-metrika-oauth-received')]
    public function handleYandexMetrikaOAuthReceived(
        ?array $settings = null,
        ?string $cacheDataId = null,
        ?int $integrationId = null
    ): void {
        $this->ensureCanEdit();

        if (is_array($settings) && $settings !== []) {
            $this->applyYandexMetrikaOAuthFromBroadcast($settings, $integrationId);

            return;
        }

        if (filled($cacheDataId)) {
            $this->finalizeYandexMetrikaOAuth($cacheDataId);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function applyYandexMetrikaOAuthTokens(array $settings): void
    {
        $this->ensureCanEdit();
        $this->ensureSelectedIntegration('yandex_metrika');

        if ($this->selectedIntegration?->integration === null) {
            return;
        }

        $integrationId = $this->selectedIntegration->integration->id;
        $existingToken = (string) ($this->selectedIntegration->settings['oauth_token'] ?? '');
        $newToken = (string) ($settings['oauth_token'] ?? '');

        if ($existingToken !== '' && $existingToken === $newToken && ($this->selectedIntegration->isEnabled ?? false)) {
            $mergedSettings = $this->selectedIntegration->settings ?? [];
        } else {
            $mergedSettings = array_merge(
                $this->selectedIntegration->settings ?? [],
                [
                    'oauth_token' => $settings['oauth_token'] ?? null,
                    'refresh_token' => $settings['refresh_token'] ?? null,
                    'token_expires_at' => $settings['token_expires_at'] ?? null,
                    'sync_enabled_at' => now()->format('Y-m-d'),
                ],
                $this->extractYandexMetrikaOAuthProfile($settings)
            );

            $this->selectedIntegration->isEnabled = true;
            $this->selectedIntegration->settings = $mergedSettings;

            $projectIntegrationData = new ProjectIntegrationData();
            $projectIntegrationData->integration = $this->selectedIntegration->integration;
            $projectIntegrationData->isEnabled = true;
            $projectIntegrationData->settings = $mergedSettings;
            $this->integrationSettings[$integrationId] = $projectIntegrationData;
            $this->refreshParameterCalculationRows();

            $this->integrationModalBodyRevision++;
            $this->markPendingChanges();
        }

        $oauthToken = (string) ($mergedSettings['oauth_token'] ?? '');
        $countersResult = $oauthToken !== ''
            ? $this->loadYandexMetrikaCounters($oauthToken)
            : ['error' => 'Сначала авторизуйтесь через Яндекс Метрику'];

        $this->dispatch(
            'yandex-metrika-oauth-applied',
            settings: array_merge(
                [
                    'oauth_token' => $mergedSettings['oauth_token'] ?? null,
                    'refresh_token' => $mergedSettings['refresh_token'] ?? null,
                    'token_expires_at' => $mergedSettings['token_expires_at'] ?? null,
                    'sync_enabled_at' => $mergedSettings['sync_enabled_at'] ?? null,
                    'is_enabled' => true,
                ],
                $this->extractYandexMetrikaOAuthProfile($mergedSettings)
            ),
            counters: $countersResult['counters'] ?? [],
            countersError: $countersResult['error'] ?? null,
            integrationId: $integrationId,
        );
    }

    /**
     * @return array{profile?: array<string, string|null>, error?: string}
     */
    public function loadYandexMetrikaOAuthProfile(string $oauthToken): array
    {
        $this->ensureCanEdit();

        if (trim($oauthToken) === '') {
            return ['error' => 'Сначала авторизуйтесь через Яндекс Метрику'];
        }

        try {
            $profile = app(YandexMetrikaAuthService::class)->fetchOauthUserProfile($oauthToken);
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Не удалось получить данные аккаунта Яндекса'];
        }

        if ($profile === null) {
            return ['error' => 'Не удалось получить данные аккаунта Яндекса'];
        }

        $this->ensureSelectedIntegration('yandex_metrika');

        if ($this->selectedIntegration?->integration !== null) {
            $integrationId = $this->selectedIntegration->integration->id;
            $mergedSettings = array_merge(
                $this->selectedIntegration->settings ?? [],
                $profile
            );

            $this->selectedIntegration->settings = $mergedSettings;

            if ($this->integrationSettings->has($integrationId)) {
                $this->integrationSettings[$integrationId]->settings = $mergedSettings;
            } else {
                $projectIntegrationData = new ProjectIntegrationData();
                $projectIntegrationData->integration = $this->selectedIntegration->integration;
                $projectIntegrationData->isEnabled = $this->selectedIntegration->isEnabled ?? false;
                $projectIntegrationData->settings = $mergedSettings;
                $this->integrationSettings[$integrationId] = $projectIntegrationData;
                $this->refreshParameterCalculationRows();
            }
        }

        return ['profile' => $profile];
    }

    /**
     * @return array{counters?: array<int, array{value: string, label: string, domain: string}>, error?: string}
     */
    public function loadYandexMetrikaCounters(string $oauthToken): array
    {
        $this->ensureCanEdit();

        if (trim($oauthToken) === '') {
            return ['error' => 'Сначала авторизуйтесь через Яндекс Метрику'];
        }

        try {
            $counters = app(YandexMetrikaAuthService::class)->listCounters($oauthToken);

            if ($counters === []) {
                return [
                    'error' => 'Не найдено доступных счётчиков Яндекс Метрики',
                ];
            }

            return ['counters' => $counters];
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Не удалось загрузить счётчики Яндекс Метрики'];
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string|null>
     */
    private function extractYandexMetrikaOAuthProfile(array $settings): array
    {
        return [
            'oauth_yandex_user_id' => isset($settings['oauth_yandex_user_id'])
                ? (string) $settings['oauth_yandex_user_id']
                : null,
            'oauth_yandex_login' => isset($settings['oauth_yandex_login'])
                ? (string) $settings['oauth_yandex_login']
                : null,
            'oauth_yandex_display_name' => isset($settings['oauth_yandex_display_name'])
                ? (string) $settings['oauth_yandex_display_name']
                : null,
            'oauth_yandex_avatar_url' => isset($settings['oauth_yandex_avatar_url'])
                ? (string) $settings['oauth_yandex_avatar_url']
                : null,
        ];
    }
}
