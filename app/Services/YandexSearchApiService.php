<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YandexSearchApiService
{
    private const DEFAULT_BASE_URL = 'https://searchapi.api.cloud.yandex.net/v2/web/search';

    private const MAX_RESULTS = 100;

    private ?string $apiKey = null;

    private ?string $folderId = null;

    public function setupClient(?string $apiKey = null, ?string $folderId = null): void
    {
        $this->apiKey = $apiKey;
        $this->folderId = $folderId;
    }

    public function validateCredentials(): bool
    {
        [$apiKey, $folderId] = $this->resolveCredentials();

        if ($apiKey === null || $apiKey === '') {
            throw new \InvalidArgumentException('Yandex Search API key is required');
        }

        if ($folderId === null || $folderId === '') {
            throw new \InvalidArgumentException('Yandex Search API folder id is required');
        }

        return true;
    }

    public function hasPlatformCredentials(): bool
    {
        try {
            $this->validateCredentials();

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @return list<array{position: int, url: string, title: string}>
     */
    public function searchWeb(string $phrase, int $regionId, int $results = self::MAX_RESULTS): array
    {
        $this->validateCredentials();
        [$apiKey, $folderId] = $this->resolveCredentials();

        $results = max(1, min(self::MAX_RESULTS, $results));
        $baseUrl = (string) config('services.yandex_search_api.base_url', self::DEFAULT_BASE_URL);
        $timeout = (int) config('services.yandex_search_api.timeout', 30);

        $payload = [
            'query' => [
                'searchType' => 'SEARCH_TYPE_RU',
                'queryText' => $phrase,
            ],
            'folderId' => $folderId,
            'region' => (string) $regionId,
            'groupSpec' => [
                'groupsOnPage' => $results,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Api-Key '.$apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout($timeout)
            ->acceptJson()
            ->post($baseUrl, $payload);

        if ($response->status() === 429 || $response->serverError()) {
            throw new RequestException($response);
        }

        if (! $response->successful()) {
            Log::warning('Yandex Search API request failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
                'phrase' => $phrase,
                'region_id' => $regionId,
            ]);

            throw new \RuntimeException(
                'Yandex Search API error HTTP '.$response->status().': '.Str::limit($response->body(), 200)
            );
        }

        return $this->parseOrganicResults($response->json() ?? []);
    }

    /**
     * @param  list<array{position: int, url: string, title: string}>  $results
     */
    public function resolvePositionForDomain(array $results, string $projectDomain): ?int
    {
        $normalizedDomain = $this->normalizeDomain($projectDomain);

        if ($normalizedDomain === '') {
            return null;
        }

        foreach ($results as $item) {
            $url = (string) ($item['url'] ?? '');
            $host = $this->normalizeDomain(parse_url($url, PHP_URL_HOST) ?: $url);

            if ($host === '') {
                continue;
            }

            if ($host === $normalizedDomain || Str::endsWith($host, '.'.$normalizedDomain)) {
                return isset($item['position']) ? (int) $item['position'] : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{position: int, url: string, title: string}>
     */
    public function parseOrganicResults(array $payload): array
    {
        $rawData = $payload['rawData'] ?? null;

        if (is_string($rawData) && $rawData !== '') {
            $decoded = base64_decode($rawData, true);
            if ($decoded !== false && str_contains($decoded, '<yandexsearch')) {
                return $this->parseXmlOrganicResults($decoded);
            }

            // Sometimes rawData is already a structured array after json_decode edge cases
        }

        if (is_array($rawData)) {
            $groups = data_get($rawData, 'groups') ?? [];
            if (is_array($groups) && $groups !== []) {
                return $this->mapGroupsToResults($groups);
            }
        }

        $groups = data_get($payload, 'groups')
            ?? data_get($payload, 'response.groups')
            ?? [];

        if (is_array($groups) && $groups !== []) {
            return $this->mapGroupsToResults($groups);
        }

        $documents = data_get($payload, 'documents')
            ?? data_get($payload, 'results')
            ?? [];

        return $this->mapDocumentsToResults(is_array($documents) ? $documents : []);
    }

    /**
     * @return list<array{position: int, url: string, title: string}>
     */
    private function parseXmlOrganicResults(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            return [];
        }

        $results = [];
        $position = 1;

        $groups = $document->xpath('//group') ?: [];
        foreach ($groups as $group) {
            $doc = $group->doc[0] ?? null;
            if ($doc === null) {
                continue;
            }

            $url = trim((string) ($doc->url ?? ''));
            if ($url === '') {
                continue;
            }

            $title = trim(html_entity_decode(strip_tags((string) ($doc->title ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $results[] = [
                'position' => $position++,
                'url' => $url,
                'title' => $title,
            ];
        }

        return $results;
    }

    /**
     * @param  array<int, mixed>  $groups
     * @return list<array{position: int, url: string, title: string}>
     */
    private function mapGroupsToResults(array $groups): array
    {
        $results = [];
        $position = 1;

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $docs = $group['documents'] ?? $group['docs'] ?? [];
            if (! is_array($docs) || $docs === []) {
                continue;
            }

            $doc = $docs[0] ?? null;
            if (! is_array($doc)) {
                continue;
            }

            $url = (string) ($doc['url'] ?? $doc['link'] ?? '');
            if ($url === '') {
                continue;
            }

            $results[] = [
                'position' => $position++,
                'url' => $url,
                'title' => (string) ($doc['title'] ?? $doc['headline'] ?? ''),
            ];
        }

        return $results;
    }

    /**
     * @param  array<int, mixed>  $documents
     * @return list<array{position: int, url: string, title: string}>
     */
    private function mapDocumentsToResults(array $documents): array
    {
        $results = [];
        $position = 1;

        foreach ($documents as $doc) {
            if (! is_array($doc)) {
                continue;
            }

            $url = (string) ($doc['url'] ?? $doc['link'] ?? '');
            if ($url === '') {
                continue;
            }

            $results[] = [
                'position' => isset($doc['position']) ? (int) $doc['position'] : $position,
                'url' => $url,
                'title' => (string) ($doc['title'] ?? $doc['headline'] ?? ''),
            ];
            $position++;
        }

        return $results;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = mb_strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = preg_replace('#^www\.#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0] ?? $domain;

        return rtrim($domain, '.');
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveCredentials(): array
    {
        $apiKey = $this->apiKey ?? config('services.yandex_search_api.api_key');
        $folderId = $this->folderId ?? config('services.yandex_search_api.folder_id');

        return [
            is_string($apiKey) ? $apiKey : null,
            is_string($folderId) ? $folderId : null,
        ];
    }
}
