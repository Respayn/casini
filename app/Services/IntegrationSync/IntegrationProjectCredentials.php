<?php

namespace App\Services\IntegrationSync;

use App\Helpers\PhraseDuplicateHelper;
use App\Repositories\IntegrationRepository;
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
