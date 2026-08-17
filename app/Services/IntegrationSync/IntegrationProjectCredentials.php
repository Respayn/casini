<?php

namespace App\Services\IntegrationSync;

use App\Clients\YandexDirect\YandexDirectOAuthClient;
use App\Helpers\PhraseDuplicateHelper;
use App\Models\IntegrationProject;
use App\Repositories\IntegrationRepository;
use App\Support\SafeLogger;
use Illuminate\Support\Collection;

/**
 * Чтение credentials активных интеграций проекта для collectors.
 */
class IntegrationProjectCredentials
{
    public function __construct(
        private readonly IntegrationRepository $integrationRepository,
    ) {}

    /**
     * @return array<string, mixed>|null settings включённой интеграции или null
     */
    public function settingsFor(int $projectId, string $integrationCode): ?array
    {
        $item = $this->projectIntegration($projectId, $integrationCode);

        if ($item === null || ! is_array($item->settings ?? null)) {
            return null;
        }

        return $item->settings;
    }

    public function hasEnabled(int $projectId, string $integrationCode): bool
    {
        return $this->projectIntegration($projectId, $integrationCode) !== null;
    }

    /**
     * @return object{settings: mixed, integration: object}|null
     */
    public function projectIntegration(int $projectId, string $integrationCode): ?object
    {
        $mapped = $this->integrationRepository->getActiveIntegrationsMappedByProjects([$projectId]);
        /** @var Collection<int, object> $list */
        $list = $mapped->get($projectId, collect());

        $item = $list->first(
            fn ($row) => ($row->integration->code ?? null) === $integrationCode
        );

        return $item;
    }

    /**
     * @return array{token: string, client_login: string}|null
     */
    public function yandexDirect(int $projectId): ?array
    {
        $settings = $this->settingsFor($projectId, 'yandex_direct');

        if ($settings === null) {
            return null;
        }

        $token = $settings['oauth_token'] ?? $settings['encryptedOauthToken'] ?? null;
        $login = $settings['client_login'] ?? $settings['clientLogin'] ?? null;

        if (! filled($token) || ! filled($login)) {
            return null;
        }

        return [
            'token' => (string) $token,
            'client_login' => (string) $login,
            'refresh_token' => filled($settings['refresh_token'] ?? $settings['encryptedRefreshToken'] ?? null)
                ? (string) ($settings['refresh_token'] ?? $settings['encryptedRefreshToken'])
                : null,
        ];
    }

    /**
     * Обновить access-токен Директа и записать его в settings проекта.
     * Без секретов в логах. null — refresh недоступен или Яндекс отклонил grant.
     */
    public function refreshYandexDirectAccessToken(int $projectId): ?string
    {
        $item = IntegrationProject::query()
            ->where('project_id', $projectId)
            ->where('is_enabled', true)
            ->whereHas('integration', fn ($query) => $query->where('code', 'yandex_direct'))
            ->first();

        if ($item === null) {
            return null;
        }

        $settings = is_array($item->settings) ? $item->settings : [];
        $refreshToken = $settings['refresh_token'] ?? $settings['encryptedRefreshToken'] ?? null;
        $clientId = config('services.yandex_direct.client_id');
        $clientSecret = config('services.yandex_direct.client_secret');

        if (! filled($refreshToken) || ! filled($clientId) || ! filled($clientSecret)) {
            return null;
        }

        try {
            $tokens = app(YandexDirectOAuthClient::class)->refreshToken(
                (string) $clientId,
                (string) $clientSecret,
                (string) $refreshToken,
            );
        } catch (\Throwable $e) {
            SafeLogger::warning('Yandex Direct token refresh failed', [
                'project_id' => $projectId,
                'message' => SafeLogger::publicMessage($e),
            ]);

            return null;
        }

        $access = $tokens['access_token'] ?? null;

        if (! filled($access)) {
            return null;
        }

        $settings['oauth_token'] = $access;

        if (filled($tokens['refresh_token'] ?? null)) {
            $settings['refresh_token'] = $tokens['refresh_token'];
        }

        if (isset($tokens['expires_in'])) {
            $settings['token_expires_at'] = now()->addSeconds((int) $tokens['expires_in'])->toDateTimeString();
        }

        $item->settings = $settings;
        $item->save();

        return (string) $access;
    }

    /**
     * @return array{email: string, token: string, site_id: int}|null
     */
    public function callibri(int $projectId): ?array
    {
        $settings = $this->settingsFor($projectId, 'callibri');

        if ($settings === null) {
            return null;
        }

        $email = $settings['email'] ?? null;
        $token = $settings['token'] ?? null;
        $siteId = $settings['site_id'] ?? $settings['siteId'] ?? null;

        if (! filled($email) || ! filled($token) || ! filled($siteId) || ! is_numeric($siteId)) {
            return null;
        }

        return [
            'email' => (string) $email,
            'token' => (string) $token,
            'site_id' => (int) $siteId,
        ];
    }

    /**
     * @return array{regions: array<int, array{code: mixed, phrases: string[]}>}|null
     */
    public function yandexSearchApi(int $projectId): ?array
    {
        $settings = $this->settingsFor($projectId, 'yandex_search_api');

        if ($settings === null) {
            return null;
        }

        $regions = $settings['regions'] ?? [];

        if (! is_array($regions) || ! PhraseDuplicateHelper::isValidForSave($regions)) {
            return null;
        }

        return [
            'regions' => $regions,
        ];
    }
}
