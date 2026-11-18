<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\YandexMetrikaGoalUtm;
use App\Services\YandexMetrikaService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm as DomainYandexMetrikaGoalUtm;
use Src\Infrastructure\Reports\YandexDirectGeneralStats;
use Tests\TestCase;

class YandexMetrikaUtmGoalsSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_command_writes_utm_source_and_campaign(): void
    {
        $project = Project::factory()->create();
        $integration = Integration::query()->where('code', 'yandex_metrika')->firstOrFail();

        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [
                'oauth_token' => 'token',
                'oauth_yandex_login' => 'login',
                'counter_id' => 12345678,
                'sync_enabled_at' => '2026-08-01',
                'goals' => [111],
                'goals_metric' => 'target_visits',
                'utm_filter_mode' => 'source',
                'utm_source' => 'yandex',
                'reports' => [
                    'goals_utm' => true,
                ],
            ],
        ]);

        $this->mock(YandexMetrikaService::class, function ($mock) {
            $mock->shouldReceive('setupClientFromSettings')->once();
            $mock->shouldReceive('fetchUtmGoalsStats')->once()->andReturn([
                [
                    'goal_name' => 'Заявка',
                    'utm_source' => 'yandex',
                    'utm_campaign' => '100',
                    'date' => '2026-08-15',
                    'value' => 2,
                ],
                [
                    'goal_name' => 'Заявка',
                    'utm_source' => 'yandex',
                    'utm_campaign' => '',
                    'date' => '2026-08-16',
                    'value' => 1,
                ],
            ]);
        });

        $this->artisan('metrika:sync', ['report' => 'goals_utm'])->assertSuccessful();

        $this->assertSame(3, YandexMetrikaGoalUtm::query()
            ->where('project_id', $project->id)
            ->count());

        $withCampaign = YandexMetrikaGoalUtm::query()
            ->where('project_id', $project->id)
            ->where('utm_campaign', '100')
            ->get();

        $this->assertCount(2, $withCampaign);
        $this->assertSame('Заявка', $withCampaign->first()->goal_name);
        $this->assertSame('yandex', $withCampaign->first()->utm_source);
        $this->assertSame('2026-08-15', $withCampaign->first()->achieved_date->format('Y-m-d'));

        $withoutCampaign = YandexMetrikaGoalUtm::query()
            ->where('project_id', $project->id)
            ->whereNull('utm_campaign')
            ->first();

        $this->assertNotNull($withoutCampaign);
        $this->assertSame('yandex', $withoutCampaign->utm_source);
        $this->assertSame('2026-08-16', $withoutCampaign->achieved_date->format('Y-m-d'));
    }

    #[Test]
    public function test_saved_campaign_id_matches_general_stats_row(): void
    {
        $stats = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 100,
                'clicks' => 10,
                'cost_with_vat' => 100.0,
                'cost_without_vat' => 80.0,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-08-15',
            ]),
        ];

        $metrika = [
            DomainYandexMetrikaGoalUtm::restore([
                'id' => 1,
                'project_id' => 1,
                'goal_name' => '',
                'achieved_date' => '2026-08-15',
                'utm_source' => 'yandex',
                'utm_medium' => null,
                'utm_campaign' => '100',
                'utm_content' => null,
                'utm_term' => null,
            ]),
            DomainYandexMetrikaGoalUtm::restore([
                'id' => 2,
                'project_id' => 1,
                'goal_name' => '',
                'achieved_date' => '2026-08-16',
                'utm_source' => 'yandex',
                'utm_medium' => null,
                'utm_campaign' => null,
                'utm_content' => null,
                'utm_term' => null,
            ]),
        ];

        $result = YandexDirectGeneralStats::build($stats, [], $metrika, false, true);

        $this->assertSame('Кампания А', $result['rows'][0]['campaign']);
        $this->assertSame('1', $result['rows'][0]['conversions']);
        $this->assertSame('Без кампании', $result['rows'][1]['campaign']);
        $this->assertSame('1', $result['rows'][1]['conversions']);
        $this->assertSame('2', $result['totals'][YandexDirectGeneralStats::TOTAL_CONVERSIONS]);
    }

    #[Test]
    public function test_command_skips_projects_without_utm_report(): void
    {
        $project = Project::factory()->create();
        $integration = Integration::query()->where('code', 'yandex_metrika')->firstOrFail();

        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [
                'oauth_token' => 'token',
                'counter_id' => 12345678,
                'sync_enabled_at' => '2026-08-01',
                'goals' => [111],
                'reports' => [
                    'goals_search_engines' => true,
                ],
            ],
        ]);

        $this->artisan('metrika:sync', ['report' => 'goals_utm'])->assertSuccessful();

        $this->assertSame(0, YandexMetrikaGoalUtm::query()
            ->where('project_id', $project->id)
            ->count());
    }
}
