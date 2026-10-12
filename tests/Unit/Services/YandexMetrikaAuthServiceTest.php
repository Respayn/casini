<?php

namespace Tests\Unit\Services;

use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use App\Services\YandexMetrikaAuthService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaAuthServiceTest extends TestCase
{
    #[Test]
    public function test_list_counters_formats_id_and_domain(): void
    {
        Http::fake([
            'api-metrika.yandex.net/management/v1/counters*' => Http::response([
                'counters' => [
                    ['id' => 111, 'site' => 'first.ru', 'time_zone_name' => 'Europe/Moscow'],
                    ['id' => 222, 'site2' => ['site' => 'second.ru'], 'time_zone_name' => 'Asia/Yekaterinburg'],
                    ['id' => 0, 'site' => 'skip.ru'],
                ],
            ]),
        ]);

        $options = app(YandexMetrikaAuthService::class)->listCounters('oauth-token');

        $this->assertCount(2, $options);
        $this->assertSame('111 (first.ru)', $options[0]['label']);
        $this->assertSame('222 (second.ru)', $options[1]['label']);
        $this->assertSame('second.ru', $options[1]['domain']);
        $this->assertSame('Europe/Moscow', $options[0]['time_zone_name']);
        $this->assertSame('Asia/Yekaterinburg', $options[1]['time_zone_name']);
    }

    #[Test]
    public function test_settings_data_defaults_to_automatic_attribution_and_without_robots(): void
    {
        $data = YandexMetrikaIntegrationSettingsData::fromSettings(collect());

        $this->assertNull($data->counterId);
        $this->assertNull($data->counterTimeZone);
        $this->assertSame('automatic', $data->attributionModel);
        $this->assertSame('without_robots', $data->dataMode);
        $this->assertNull($data->filters['entry_page']);
        $this->assertFalse($data->reports['goals_search_engines']);
        $this->assertSame([], $data->goals);
        $this->assertSame('target_visits', $data->goalsMetric);

        $withGoals = YandexMetrikaIntegrationSettingsData::fromSettings(collect([
            'goals' => ['111', 0, 222, 222],
            'goals_metric' => 'goal_reaches',
        ]));
        $this->assertSame([111, 222], $withGoals->goals);
        $this->assertSame('goal_reaches', $withGoals->goalsMetric);
        $this->assertNull($data->encryptedOauthToken);
    }
}
