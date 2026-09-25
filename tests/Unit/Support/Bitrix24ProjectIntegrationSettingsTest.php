<?php

namespace Tests\Unit\Support;

use App\Data\IntegrationSettings\Bitrix24IntegrationSettingsData;
use App\Factories\IntegrationSettingsFactory;
use App\Support\Bitrix24AgencyConnection;
use App\Support\Bitrix24ProjectSettingsValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Bitrix24ProjectIntegrationSettingsTest extends TestCase
{
    #[Test]
    public function test_factory_returns_default_bitrix24_settings(): void
    {
        $settings = IntegrationSettingsFactory::create('bitrix24');

        $this->assertInstanceOf(Bitrix24IntegrationSettingsData::class, $settings);

        $array = $settings->toArray();

        $this->assertSame('', $array['root_task'] ?? null);
        $this->assertSame('', $array['search_query'] ?? null);
        $this->assertFalse($array['parse_comment_works'] ?? true);
        $this->assertTrue(
            ! array_key_exists('sync_enabled_at', $array) || $array['sync_enabled_at'] === null
        );
    }

    #[Test]
    public function test_agency_is_configured_only_with_both_url_and_webhook(): void
    {
        $this->assertFalse(Bitrix24AgencyConnection::isConfigured(null, null));
        $this->assertFalse(Bitrix24AgencyConnection::isConfigured('https://company.bitrix24.ru', null));
        $this->assertFalse(Bitrix24AgencyConnection::isConfigured('https://company.bitrix24.ru', ''));
        $this->assertFalse(Bitrix24AgencyConnection::isConfigured('', 'https://company.bitrix24.ru/rest/1/secret/'));
        $this->assertFalse(Bitrix24AgencyConnection::isConfigured(null, 'https://company.bitrix24.ru/rest/1/secret/'));

        $this->assertTrue(Bitrix24AgencyConnection::isConfigured(
            'https://company.bitrix24.ru',
            'https://company.bitrix24.ru/rest/1/secret/'
        ));
    }

    #[Test]
    public function test_enabled_sync_requires_root_task(): void
    {
        $errors = Bitrix24ProjectSettingsValidator::errors(true, [
            'root_task' => '',
            'search_query' => 'SEO:, КР:',
            'parse_comment_works' => false,
        ]);

        $this->assertArrayHasKey('root_task', $errors);
    }

    #[Test]
    public function test_disabled_sync_allows_empty_root_task(): void
    {
        $errors = Bitrix24ProjectSettingsValidator::errors(false, [
            'root_task' => '',
            'search_query' => '',
            'parse_comment_works' => false,
        ]);

        $this->assertSame([], $errors);
    }

    #[Test]
    public function test_invalid_root_task_url_is_rejected(): void
    {
        $errors = Bitrix24ProjectSettingsValidator::errors(true, [
            'root_task' => 'не ссылка',
            'search_query' => '',
            'parse_comment_works' => false,
        ]);

        $this->assertSame(
            Bitrix24ProjectSettingsValidator::ROOT_TASK_URL_ERROR,
            $errors['root_task'] ?? null
        );
    }

    #[Test]
    public function test_normalize_keeps_search_query_and_default_parse_flag(): void
    {
        $normalized = Bitrix24ProjectSettingsValidator::normalize([
            'root_task' => '  https://company.bitrix24.ru/tasks/task/view/123/  ',
            'search_query' => 'SEO:, КР:',
            'sync_enabled_at' => '2026-01-15',
        ], false);

        $this->assertSame('https://company.bitrix24.ru/tasks/task/view/123/', $normalized['root_task']);
        $this->assertSame('SEO:, КР:', $normalized['search_query']);
        $this->assertFalse($normalized['parse_comment_works']);
        $this->assertArrayNotHasKey('sync_enabled_at', $normalized);

        $withParse = Bitrix24ProjectSettingsValidator::normalize([
            'root_task' => 'https://company.bitrix24.ru/tasks/task/view/1/',
            'search_query' => '',
            'parse_comment_works' => true,
            'sync_enabled_at' => '2026-06-17',
        ], true);

        $this->assertTrue($withParse['parse_comment_works']);
        $this->assertSame('2026-06-17', $withParse['sync_enabled_at']);
    }

    #[Test]
    public function test_enabled_sync_without_agency_connection_is_rejected(): void
    {
        $errors = Bitrix24ProjectSettingsValidator::errors(
            true,
            [
                'root_task' => 'https://company.bitrix24.ru/tasks/task/view/100/',
                'search_query' => '',
                'parse_comment_works' => false,
            ],
            false
        );

        $this->assertArrayHasKey('is_enabled', $errors);
        $this->assertStringContainsString('настройках агентства', $errors['is_enabled']);
    }
}
