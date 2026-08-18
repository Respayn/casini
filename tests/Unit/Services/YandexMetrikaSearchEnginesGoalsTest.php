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

class YandexMetrikaSearchEnginesGoalsTest extends TestCase
{
    #[Test]
    public function test_fetch_sums_goal_metrics_and_normalizes_engines(): void
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
                        'dimensions' => [['name' => 'Yandex']],
                        'metrics' => [10, 5],
                    ],
                    [
                        'dimensions' => [['name' => 'Яндекс']],
                        'metrics' => [2],
                    ],
                    [
                        'dimensions' => [['name' => 'Google']],
                        'metrics' => [3],
                    ],
                    [
                        'dimensions' => [['name' => 'Bing']],
                        'metrics' => [1],
                    ],
                ],
            ]);

        $service = $this->makeService($client);
        $rows = $service->fetchSearchEnginesGoalsStats(
            Carbon::parse('2026-08-18'),
            Carbon::parse('2026-08-18'),
            [111, 222],
            YandexMetrikaIntegrationSettingsData::GOALS_METRIC_TARGET_VISITS,
            null,
            YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
            'automatic'
        );

        $this->assertSame('ym:s:goal111visits,ym:s:goal222visits', $captured['metrics']);
        $this->assertSame('ym:s:searchEngine', $captured['dimensions']);
        $this->assertSame('automatic', $captured['attribution']);
        $this->assertStringContainsString("ym:s:isRobot=='No'", (string) $captured['filters']);

        $byEngine = [];
        foreach ($rows as $row) {
            $byEngine[$row['search_engine']] = $row;
        }

        $this->assertSame(17, $byEngine['yandex']['value']);
        $this->assertSame('2026-08-01', $byEngine['yandex']['month']);
        $this->assertSame(3, $byEngine['google']['value']);
        $this->assertSame(1, $byEngine['other']['value']);
    }

    #[Test]
    public function test_fetch_uses_reaches_metrics_and_month_dimension(): void
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
                        'dimensions' => [['name' => 'Yandex'], ['name' => '2026-07']],
                        'metrics' => [4],
                    ],
                    [
                        'dimensions' => [['name' => 'Yandex'], ['name' => '2026-08']],
                        'metrics' => [9],
                    ],
                ],
            ]);

        $service = $this->makeService($client);
        $rows = $service->fetchSearchEnginesGoalsStats(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-08-18'),
            [333],
            YandexMetrikaIntegrationSettingsData::GOALS_METRIC_GOAL_REACHES
        );

        $this->assertSame('ym:s:goal333reaches', $captured['metrics']);
        $this->assertSame('ym:s:searchEngine,ym:s:month', $captured['dimensions']);

        $this->assertCount(2, $rows);
        $this->assertSame('2026-07-01', $rows[0]['month']);
        $this->assertSame(4, $rows[0]['value']);
        $this->assertSame('2026-08-01', $rows[1]['month']);
        $this->assertSame(9, $rows[1]['value']);
    }

    #[Test]
    public function test_count_for_date_sums_all_engines(): void
    {
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')->once()->andReturn([
            'data' => [
                ['dimensions' => [['name' => 'Yandex']], 'metrics' => [7]],
                ['dimensions' => [['name' => 'Google']], 'metrics' => [2]],
            ],
        ]);

        $count = $this->makeService($client)->countSearchEnginesGoalsForDate([
            'oauth_token' => 'token',
            'oauth_yandex_login' => 'login',
            'counter_id' => 123,
            'goals' => [111],
            'goals_metric' => 'target_visits',
            'data_mode' => 'with_robots',
            'attribution_model' => 'lastsign',
        ], Carbon::parse('2026-08-18'));

        $this->assertSame(9, $count);
    }

    #[Test]
    public function test_list_goal_options_skips_invalid_ids(): void
    {
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getGoals')->once()->andReturn([
            'goals' => [
                ['id' => 10, 'name' => 'Заявка'],
                ['id' => 0, 'name' => 'skip'],
                ['id' => 20],
            ],
        ]);

        $options = $this->makeService($client)->listGoalOptions();

        $this->assertSame([
            ['id' => 10, 'name' => 'Заявка'],
            ['id' => 20, 'name' => 'Цель 20'],
        ], $options);
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
