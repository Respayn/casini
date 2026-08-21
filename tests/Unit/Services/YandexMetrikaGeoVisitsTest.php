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

class YandexMetrikaGeoVisitsTest extends TestCase
{
    #[Test]
    public function test_fetch_uses_visits_metric_and_city_dimension(): void
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
                        'dimensions' => [['name' => 'Москва']],
                        'metrics' => [120],
                    ],
                    [
                        'dimensions' => [['name' => 'Екатеринбург']],
                        'metrics' => [45],
                    ],
                ],
            ]);

        $rows = $this->makeService($client)->fetchGeoVisitsStats(
            Carbon::parse('2026-08-18'),
            Carbon::parse('2026-08-18'),
            YandexMetrikaIntegrationSettingsData::VISITS_METRIC_VISITS
        );

        $this->assertSame('ym:s:visits', $captured['metrics']);
        $this->assertSame('ym:s:regionCity', $captured['dimensions']);
        $this->assertStringContainsString("ym:s:isRobot=='No'", (string) ($captured['filters'] ?? ''));
        $this->assertSame('Москва', $rows[0]['city']);
        $this->assertSame(120, $rows[0]['visits']);
        $this->assertSame(0, $rows[0]['visitors']);
        $this->assertSame(120, $rows[0]['value']);
        $this->assertSame(45, $rows[1]['value']);
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
                        'dimensions' => [['name' => 'Москва'], ['name' => '2026-07']],
                        'metrics' => [10],
                    ],
                    [
                        'dimensions' => [['name' => 'Москва'], ['name' => '2026-08']],
                        'metrics' => [20],
                    ],
                ],
            ]);

        $rows = $this->makeService($client)->fetchGeoVisitsStats(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-08-18'),
            YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS
        );

        $this->assertSame('ym:s:users', $captured['metrics']);
        $this->assertSame('ym:s:regionCity,ym:s:month', $captured['dimensions']);
        $this->assertCount(2, $rows);
        $this->assertSame(0, $rows[0]['visits']);
        $this->assertSame(10, $rows[0]['visitors']);
        $this->assertSame('2026-07-01', $rows[0]['month']);
        $this->assertSame('2026-08-01', $rows[1]['month']);
    }

    #[Test]
    public function test_count_for_date_sums_values(): void
    {
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')->once()->andReturn([
            'data' => [
                ['dimensions' => [['name' => 'Москва']], 'metrics' => [3]],
                ['dimensions' => [['name' => 'Казань']], 'metrics' => [5]],
            ],
        ]);

        $count = $this->makeService($client)->countGeoVisitsForDate([
            'oauth_token' => 'token',
            'oauth_yandex_login' => 'login',
            'counter_id' => 123,
            'visits_metric' => 'visits',
            'data_mode' => 'with_robots',
            'attribution_model' => 'lastsign',
        ], Carbon::parse('2026-08-18'));

        $this->assertSame(8, $count);
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
