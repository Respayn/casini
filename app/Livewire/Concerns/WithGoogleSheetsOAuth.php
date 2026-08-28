<?php

namespace App\Livewire\Concerns;

use App\Data\ProjectForm\ProjectIntegrationData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

trait WithGoogleSheetsOAuth
{
    #[Computed]
    public function isGoogleSheetsOAuthConfigured(): bool
    {
        return filled(config('services.google_sheets.client_id'))
            && filled(config('services.google_sheets.client_secret'));
    }

    /**
     * @return array{url?: string, cache_data_id?: string, error?: string}
     */
    public function prepareGoogleSheetsOAuth(bool $popup = true): array
    {
        $this->ensureCanEdit();
        $this->ensureSelectedIntegration('google_sheets');

        if (! $this->isGoogleSheetsOAuthConfigured) {
            return [
                'error' => 'Интеграция Google Таблицы не настроена на сервере. Обратитесь к администратору.',
            ];
        }

        $cacheDataId = (string) Str::uuid();

        Cache::put(
            'integration_data_'.$cacheDataId,
            $this->buildOAuthCachePayload(),
            now()->addMinutes(15)
        );

        $url = route('google_sheets.oauth.redirect', [
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
    public function pullGoogleSheetsOAuthResult(string $cacheDataId): array
    {
        $this->ensureCanEdit();

        if (trim($cacheDataId) === '') {
            return ['pending' => true];
        }

        $settings = Cache::pull('google_sheets_oauth_result_'.$cacheDataId);

        if (! is_array($settings) || $settings === []) {
            return ['pending' => true];
        }

        return ['settings' => $settings];
    }

    /**
     * @return array{applied?: bool, pending?: bool}
     */
    public function finalizeGoogleSheetsOAuth(string $cacheDataId): array
    {
        $this->ensureCanEdit();

        $cacheDataId = trim($cacheDataId);

        if ($cacheDataId === '') {
            return ['pending' => true];
        }

        $settings = Cache::pull('google_sheets_oauth_result_'.$cacheDataId);

        if (! is_array($settings) || $settings === []) {
            return ['pending' => true];
        }

        $this->selectIntegration('google_sheets');
        $this->applyGoogleSheetsOAuthTokens($settings);
        $this->dispatch('modal-show', name: 'integration-settings-modal');

        $uiSettings = $this->buildGoogleSheetsOAuthUiSettings(
            $this->selectedIntegration?->settings ?? $settings
        );

        return [
            'applied' => true,
            'settings' => $uiSettings,
            'integrationId' => (int) ($this->selectedIntegration?->integration?->id ?? 0),
        ];
    }

    /**
     * @return array{settings?: array<string, mixed>, integrationId?: int}
     */
    public function getGoogleSheetsOAuthUiState(): array
    {
        $this->ensureCanEdit();

        $integration = $this->integrations()->firstWhere('code', 'google_sheets');

        if ($integration === null) {
            return [];
        }

        $projectIntegration = $this->integrationSettings->get($integration->id)
            ?? $this->selectedIntegration;

        if ($projectIntegration?->integration?->code !== 'google_sheets') {
            return [];
        }

        $settings = $this->buildGoogleSheetsOAuthUiSettings($projectIntegration->settings ?? []);

        if (blank($settings['oauth_token'] ?? null)) {
            return [];
        }

        return [
            'settings' => $settings,
            'integrationId' => $integration->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function buildGoogleSheetsOAuthUiSettings(array $settings): array
    {
        return array_merge(
            [
                'oauth_token' => $settings['oauth_token'] ?? null,
                'refresh_token' => $settings['refresh_token'] ?? null,
                'token_expires_at' => $settings['token_expires_at'] ?? null,
                'sync_enabled_at' => $settings['sync_enabled_at'] ?? null,
                'document_id' => $settings['document_id'] ?? $settings['documentId'] ?? null,
                'is_enabled' => true,
            ],
            $this->extractGoogleSheetsOAuthProfile($settings)
        );
    }

    /**
     * @return array{applied: bool}
     */
    public function applyGoogleSheetsOAuthFromBroadcast(array $settings, ?int $integrationId = null): array
    {
        $this->ensureCanEdit();

        $this->selectIntegration('google_sheets');

        $selectedIntegrationId = (int) ($this->selectedIntegration?->integration?->id ?? 0);
        $expectedIntegrationId = $integrationId !== null ? (int) $integrationId : null;

        if ($expectedIntegrationId !== null && $selectedIntegrationId !== $expectedIntegrationId) {
            return ['applied' => false];
        }

        if ($settings === []) {
            return ['applied' => false];
        }

        $this->applyGoogleSheetsOAuthTokens($settings);
        $this->dispatch('modal-show', name: 'integration-settings-modal');

        return [
            'applied' => true,
            'settings' => $this->buildGoogleSheetsOAuthUiSettings($this->selectedIntegration?->settings ?? $settings),
            'integrationId' => $selectedIntegrationId,
        ];
    }

    #[On('google-sheets-oauth-received')]
    public function handleGoogleSheetsOAuthReceived(
        ?array $settings = null,
        ?string $cacheDataId = null,
        ?int $integrationId = null
    ): void {
        $this->ensureCanEdit();

        if (is_array($settings) && $settings !== []) {
            $this->applyGoogleSheetsOAuthFromBroadcast($settings, $integrationId);

            return;
        }

        if (filled($cacheDataId)) {
            $this->finalizeGoogleSheetsOAuth($cacheDataId);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function applyGoogleSheetsOAuthTokens(array $settings): void
    {
        $this->ensureCanEdit();
        $this->ensureSelectedIntegration('google_sheets');

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
                $this->extractGoogleSheetsOAuthProfile($settings)
            );

            $this->selectedIntegration->isEnabled = true;
            $this->selectedIntegration->settings = $mergedSettings;

            $projectIntegrationData = new ProjectIntegrationData;
            $projectIntegrationData->integration = $this->selectedIntegration->integration;
            $projectIntegrationData->isEnabled = true;
            $projectIntegrationData->settings = $mergedSettings;
            $this->integrationSettings[$integrationId] = $projectIntegrationData;
            $this->integrationSettings = $this->integrationSettings->mapWithKeys(
                fn ($setting, $id) => [(int) $id => $setting]
            );
            $this->selectedIntegration = $this->integrationSettings->get($integrationId);
            unset($this->configuredMoneyIntegrations, $this->configuredAnalyticsIntegrations, $this->configuredToolsIntegrations);
            $this->refreshParameterCalculationRows();

            $this->integrationModalBodyRevision++;
            $this->markPendingChanges();
        }

        $this->dispatch(
            'google-sheets-oauth-applied',
            settings: $this->buildGoogleSheetsOAuthUiSettings($mergedSettings),
            integrationId: $integrationId,
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string|null>
     */
    private function extractGoogleSheetsOAuthProfile(array $settings): array
    {
        return [
            'oauth_google_user_id' => isset($settings['oauth_google_user_id'])
                ? (string) $settings['oauth_google_user_id']
                : null,
            'oauth_google_email' => isset($settings['oauth_google_email'])
                ? (string) $settings['oauth_google_email']
                : null,
            'oauth_google_display_name' => isset($settings['oauth_google_display_name'])
                ? (string) $settings['oauth_google_display_name']
                : null,
            'oauth_google_avatar_url' => isset($settings['oauth_google_avatar_url'])
                ? (string) $settings['oauth_google_avatar_url']
                : null,
        ];
    }
}
