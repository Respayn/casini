<?php

namespace Tests\Unit\Services;

use App\Contracts\YandexMetrikaClientInterface;
use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Factories\YandexMetrikaClientFactory;
use App\Repositories\AgencyRepository;
use App\Services\YandexMetrikaService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\YandexMetrika\YandexMetrikaFiltersBuilder;
use Tests\TestCase;

class YandexMetrikaUtmGoalsTest extends TestCase
{
    #[Test]
    public function test_fetch_requests_source_and_campaign_and_returns_both(): void
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
                        'dimensions' => [
                            ['name' => 'yandex_sa'],
                            ['name' => '100'],
                            ['name' => '2026-10-15'],
                        ],
                        'metrics' => [2],
                    ],
                    [
                        'dimensions' => [
                            ['name' => 'yandex_sa'],
                            ['name' => ''],
                            ['name' => '2026-10-16'],
                        ],
                        'metrics' => [1],
                    ],
                ],
            ]);

        $service = $this->makeService($client);
        $rows = $service->fetchUtmGoalsStats(
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            [111],
            YandexMetrikaIntegrationSettingsData::GOALS_METRIC_TARGET_VISITS,
            'source',
            'yandex_sa',
            null,
            YandexMetrikaIntegrationSettingsData::DEFAULT_DATA_MODE,
            'automatic'
        );

        $this->assertSame(
            'ym:s:<attribution>UTMSource,ym:s:<attribution>UTMCampaign,ym:s:date',
            $captured['dimensions']
        );
        $this->assertStringContainsString("ym:s:<attribution>UTMSource=@'yandex_sa'", (string) $captured['filters']);

        $this->assertSame([
            [
                'utm_source' => 'yandex_sa',
                'utm_campaign' => '100',
                'date' => '2026-10-15',
                'value' => 2,
            ],
            [
                'utm_source' => 'yandex_sa',
                'utm_campaign' => '',
                'date' => '2026-10-16',
                'value' => 1,
            ],
        ], $rows);
    }

    private function makeService(YandexMetrikaClientInterface $client): YandexMetrikaService
    {
        $factory = Mockery::mock(YandexMetrikaClientFactory::class);
        $factory->shouldReceive('create')->andReturn($client);

        $service = new YandexMetrikaService($factory, new YandexMetrikaFiltersBuilder, $this->createStub(AgencyRepository::class));
        $service->setupClient('token', 'login', 123);

        return $service;
    }
}
