<?php

namespace Tests\Unit\Services;

use App\Contracts\YandexMetrikaClientInterface;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Factories\YandexMetrikaClientFactory;
use App\Services\YandexMetrikaService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\YandexMetrika\YandexMetrikaFiltersBuilder;
use Tests\TestCase;

class YandexMetrikaSearchEnginesVisitsTest extends TestCase
{
    #[Test]
    public function test_fetch_uses_visits_metric_and_root_ids(): void
    {
        $captured = [];
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')
            ->once()
            ->with(Mockery::on(function (array $params) use (&$captured) {
                $captured = $params;

                return true;
            }))
            ->andReturn([
                'data' => [
                    [
                        'dimensions' => [['id' => 'yandex', 'name' => 'Яндекс']],
                        'metrics' => [12],
                    ],
                    [
                        'dimensions' => [['id' => 'google', 'name' => 'Google']],
                        'metrics' => [4],
                    ],
                ],
            ]);

        $service = $this->makeService($client);
        $rows = $service->fetchSearchEnginesVisitsStats(
            Carbon::parse('2026-08-18'),
            Carbon::parse('2026-08-18'),
            YandexMetrikaIntegrationSettingsData::VISITS_METRIC_VISITS
        );

        $this->assertSame('ym:s:visits', $captured['metrics']);
        $this->assertSame('ym:s:searchEngineRoot', $captured['dimensions']);
        $this->assertStringNotContainsString('searchEngineRoot=@', (string) ($captured['filters'] ?? ''));
        $this->assertSame('yandex', $rows[0]['search_engine']);
        $this->assertSame('Яндекс', $rows[0]['search_engine_label']);
        $this->assertSame(12, $rows[0]['value']);
        $this->assertSame('google', $rows[1]['search_engine']);
        $this->assertSame(4, $rows[1]['value']);
    }

    #[Test]
    public function test_fetch_uses_users_metric_and_month_dimension(): void
    {
        $captured = [];
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')
            ->once()
            ->with(Mockery::on(function (array $params) use (&$captured) {
                $captured = $params;

                return true;
            }))
            ->andReturn([
                'data' => [
                    [
                        'dimensions' => [['id' => 'yandex', 'name' => 'Яндекс'], ['name' => '2026-07']],
                        'metrics' => [8],
                    ],
                    [
                        'dimensions' => [['id' => 'yandex', 'name' => 'Яндекс'], ['name' => '2026-08']],
                        'metrics' => [11],
                    ],
                ],
            ]);

        $service = $this->makeService($client);
        $rows = $service->fetchSearchEnginesVisitsStats(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-08-18'),
            YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS
        );

        $this->assertSame('ym:s:users', $captured['metrics']);
        $this->assertSame('ym:s:searchEngineRoot,ym:s:month', $captured['dimensions']);
        $this->assertCount(2, $rows);
        $this->assertSame('2026-07-01', $rows[0]['month']);
        $this->assertSame(8, $rows[0]['value']);
        $this->assertSame('2026-08-01', $rows[1]['month']);
        $this->assertSame(11, $rows[1]['value']);
    }

    #[Test]
    public function test_fetch_applies_search_engine_root_filter(): void
    {
        $captured = [];
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')
            ->once()
            ->with(Mockery::on(function (array $params) use (&$captured) {
                $captured = $params;

                return true;
            }))
            ->andReturn([
                'data' => [
                    ['dimensions' => [['id' => 'yandex', 'name' => 'Яндекс']], 'metrics' => [10]],
                ],
            ]);

        $rows = $this->makeService($client)->fetchSearchEnginesVisitsStats(
            Carbon::parse('2026-08-18'),
            Carbon::parse('2026-08-18'),
            YandexMetrikaIntegrationSettingsData::VISITS_METRIC_VISITS,
            null,
            YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
            YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
            null,
            null,
            false,
            ['yandex']
        );

        $this->assertStringContainsString("ym:s:searchEngineRoot=@'yandex'", (string) ($captured['filters'] ?? ''));
        $this->assertCount(1, $rows);
        $this->assertSame('yandex', $rows[0]['search_engine']);
        $this->assertSame(10, $rows[0]['value']);
    }

    #[Test]
    public function test_list_search_engine_root_options(): void
    {
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')->once()->andReturn([
            'data' => [
                ['dimensions' => [['id' => 'google', 'name' => 'Google']], 'metrics' => [2]],
                ['dimensions' => [['id' => 'yandex', 'name' => 'Яндекс']], 'metrics' => [5]],
            ],
        ]);

        $options = $this->makeService($client)->listSearchEngineRootOptions(
            Carbon::parse('2026-07-20'),
            Carbon::parse('2026-08-18')
        );

        $this->assertSame([
            ['id' => 'google', 'name' => 'Google'],
            ['id' => 'yandex', 'name' => 'Яндекс'],
        ], $options);
    }

    #[Test]
    public function test_count_for_date_sums_all_engines(): void
    {
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')->once()->andReturn([
            'data' => [
                ['dimensions' => [['id' => 'yandex', 'name' => 'Яндекс']], 'metrics' => [7]],
                ['dimensions' => [['id' => 'google', 'name' => 'Google']], 'metrics' => [2]],
            ],
        ]);

        $count = $this->makeService($client)->countSearchEnginesVisitsForDate([
            'oauth_token' => 'token',
            'oauth_yandex_login' => 'login',
            'counter_id' => 123,
            'visits_metric' => 'visits',
            'data_mode' => 'with_robots',
            'attribution_model' => 'lastsign',
            'search_engines_all' => true,
        ], Carbon::parse('2026-08-18'));

        $this->assertSame(9, $count);
    }

    private function makeService(YandexMetrikaClientInterface $client): YandexMetrikaService
    {
        $factory = Mockery::mock(YandexMetrikaClientFactory::class);
        $factory->shouldReceive('create')->andReturn($client);

        $service = new YandexMetrikaService($factory, new YandexMetrikaFiltersBuilder());
        $service->setupClient('token', 'login', 123);

        return $service;
    }
}
