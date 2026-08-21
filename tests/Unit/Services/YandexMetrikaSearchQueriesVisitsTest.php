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

class YandexMetrikaSearchQueriesVisitsTest extends TestCase
{
    #[Test]
    public function test_fetch_uses_visits_metric_and_phrase_dimension(): void
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
                        'dimensions' => [['name' => 'купить диван']],
                        'metrics' => [12],
                    ],
                    [
                        'dimensions' => [['name' => 'вакансии']],
                        'metrics' => [4],
                    ],
                ],
            ]);

        $rows = $this->makeService($client)->fetchSearchQueriesVisitsStats(
            Carbon::parse('2026-08-18'),
            Carbon::parse('2026-08-18'),
            YandexMetrikaIntegrationSettingsData::VISITS_METRIC_VISITS
        );

        $this->assertSame('ym:s:visits', $captured['metrics']);
        $this->assertSame('ym:s:<attribution>SearchPhrase', $captured['dimensions']);
        $this->assertSame('купить диван', $rows[0]['phrase']);
        $this->assertSame(12, $rows[0]['visits']);
        $this->assertSame(0, $rows[0]['visitors']);
        $this->assertSame(12, $rows[0]['value']);
    }

    #[Test]
    public function test_fetch_uses_users_metric_and_minus_filter(): void
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
                        'dimensions' => [['name' => 'купить диван']],
                        'metrics' => [7],
                    ],
                ],
            ]);

        $rows = $this->makeService($client)->fetchSearchQueriesVisitsStats(
            Carbon::parse('2026-08-18'),
            Carbon::parse('2026-08-18'),
            YandexMetrikaIntegrationSettingsData::VISITS_METRIC_USERS,
            null,
            YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
            YandexMetrikaIntegrationSettingsData::DEFAULT_ATTRIBUTION_MODEL,
            null,
            null,
            "Вакансии\nРеквизиты"
        );

        $this->assertSame('ym:s:users', $captured['metrics']);
        $this->assertStringContainsString("ym:s:<attribution>SearchPhrase!@'Вакансии'", (string) ($captured['filters'] ?? ''));
        $this->assertStringContainsString("ym:s:<attribution>SearchPhrase!@'Реквизиты'", (string) ($captured['filters'] ?? ''));
        $this->assertSame(0, $rows[0]['visits']);
        $this->assertSame(7, $rows[0]['visitors']);
        $this->assertSame(7, $rows[0]['value']);
    }

    #[Test]
    public function test_count_for_date_sums_values(): void
    {
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')->once()->andReturn([
            'data' => [
                ['dimensions' => [['name' => 'а']], 'metrics' => [3]],
                ['dimensions' => [['name' => 'б']], 'metrics' => [5]],
            ],
        ]);

        $count = $this->makeService($client)->countSearchQueriesVisitsForDate([
            'oauth_token' => 'token',
            'oauth_yandex_login' => 'login',
            'counter_id' => 123,
            'visits_metric' => 'visits',
            'data_mode' => 'with_robots',
            'attribution_model' => 'lastsign',
            'search_queries_minus' => '',
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
