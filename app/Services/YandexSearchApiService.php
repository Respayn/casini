<?php

namespace App\Services;

class YandexSearchApiService
{
    private ?string $apiKey = null;

    private ?string $folderId = null;

    public function setupClient(?string $apiKey = null, ?string $folderId = null): void
    {
        $this->apiKey = $apiKey;
        $this->folderId = $folderId;
    }

    public function validateCredentials(): bool
    {
        $apiKey = $this->apiKey ?? config('services.yandex_search_api.api_key');
        $folderId = $this->folderId ?? config('services.yandex_search_api.folder_id');

        if ($apiKey === null || $apiKey === '') {
            throw new \InvalidArgumentException('Yandex Search API key is required');
        }

        if ($folderId === null || $folderId === '') {
            throw new \InvalidArgumentException('Yandex Search API folder id is required');
        }

        return true;
    }
}
