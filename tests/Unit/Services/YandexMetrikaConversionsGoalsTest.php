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

class YandexMetrikaConversionsGoalsTest extends TestCase
{
    #[Test]
    public function test_fetch_sums_goal_metrics_by_goal_name(): void
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
                        'dimensions' => [['name' => 'Заявка']],
                        'metrics' => [10, 5],
                    ],
                    [
                        'dimensions' => [['name' => 'Заявка']],
                        'metrics' => [2],
                    ],
                    [
                        'dimensions' => [['name' => 'Звонок']],
                        'metrics' => [3],
                    ],
                ],
            ]);

        $service = $this->makeService($client);
        $rows = $service->fetchConversionsGoalsStats(
            Carbon::parse('2026-08-18'),
            Carbon::parse('2026-08-18'),
            [111, 222],
            YandexMetrikaIntegrationSettingsData::GOALS_METRIC_TARGET_VISITS,
            null,
            YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
            'automatic'
        );

        $this->assertSame('ym:s:goal111visits,ym:s:goal222visits', $captured['metrics']);
        $this->assertSame('ym:s:goal', $captured['dimensions']);
        $this->assertSame('automatic', $captured['attribution']);
        $this->assertStringContainsString("ym:s:isRobot=='No'", (string) $captured['filters']);

        $byGoal = [];
        foreach ($rows as $row) {
            $byGoal[$row['goal_name']] = $row;
        }

        $this->assertSame(17, $byGoal['Заявка']['value']);
        $this->assertSame('2026-08-01', $byGoal['Заявка']['month']);
        $this->assertSame(3, $byGoal['Звонок']['value']);
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
                        'dimensions' => [['name' => 'Заявка'], ['name' => '2026-07']],
                        'metrics' => [4],
                    ],
                    [
                        'dimensions' => [['name' => 'Заявка'], ['name' => '2026-08']],
                        'metrics' => [9],
                    ],
                ],
            ]);

        $service = $this->makeService($client);
        $rows = $service->fetchConversionsGoalsStats(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-08-18'),
            [333],
            YandexMetrikaIntegrationSettingsData::GOALS_METRIC_GOAL_REACHES
        );

        $this->assertSame('ym:s:goal333reaches', $captured['metrics']);
        $this->assertSame('ym:s:goal,ym:s:month', $captured['dimensions']);

        $this->assertCount(2, $rows);
        $this->assertSame('Заявка', $rows[0]['goal_name']);
        $this->assertSame('2026-07-01', $rows[0]['month']);
        $this->assertSame(4, $rows[0]['value']);
        $this->assertSame('2026-08-01', $rows[1]['month']);
        $this->assertSame(9, $rows[1]['value']);
    }

    #[Test]
    public function test_count_for_date_sums_all_goals(): void
    {
        $client = Mockery::mock(YandexMetrikaClientInterface::class);
        $client->shouldReceive('getVisitsReport')->once()->andReturn([
            'data' => [
                ['dimensions' => [['name' => 'Заявка']], 'metrics' => [7]],
                ['dimensions' => [['name' => 'Звонок']], 'metrics' => [2]],
            ],
        ]);

        $count = $this->makeService($client)->countConversionsGoalsForDate([
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

    private function makeService(YandexMetrikaClientInterface $client): YandexMetrikaService
    {
        $factory = Mockery::mock(YandexMetrikaClientFactory::class);
        $factory->shouldReceive('create')->andReturn($client);

        $service = new YandexMetrikaService($factory, new YandexMetrikaFiltersBuilder());
        $service->setupClient('token', 'login', 123);

        return $service;
    }
}
