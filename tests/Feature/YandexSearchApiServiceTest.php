<?php

namespace Tests\Feature;

use App\Services\YandexSearchApiService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexSearchApiServiceTest extends TestCase
{
    #[Test]
    #[Group('external-api')]
    public function test_validate_credentials_requires_api_key_and_folder(): void
    {
        config([
            'services.yandex_search_api.api_key' => null,
            'services.yandex_search_api.folder_id' => null,
        ]);

        $service = app(YandexSearchApiService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->validateCredentials();
    }

    #[Test]
    #[Group('external-api')]
    public function test_validate_credentials_with_config_values(): void
    {
        $apiKey = config('services.yandex_search_api.api_key')
            ?: config('services.yandex_search_api.test_token');
        $folderId = config('services.yandex_search_api.folder_id')
            ?: config('services.yandex_search_api.test_folder_id');

        if (! $apiKey || ! $folderId) {
            $this->markTestSkipped('YANDEX_SEARCH_API_API_KEY/FOLDER_ID or TEST_* is not configured.');
        }

        config([
            'services.yandex_search_api.api_key' => $apiKey,
            'services.yandex_search_api.folder_id' => $folderId,
        ]);

        $service = app(YandexSearchApiService::class);

        $this->assertTrue($service->validateCredentials());
    }

    #[Test]
    #[Group('external-api')]
    public function test_validate_credentials_with_explicit_setup(): void
    {
        $service = app(YandexSearchApiService::class);
        $service->setupClient('test-api-key', 'test-folder-id');

        $this->assertTrue($service->validateCredentials());
    }
}
