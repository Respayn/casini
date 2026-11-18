<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Enums\IntegrationCategory;
use App\Models\Project;
use App\Models\YandexDirectCampaignStats;
use App\Repositories\IntegrationRepository;
use App\Repositories\ProjectRepository;
use App\Services\IntegrationSync\Collectors\YandexDirectCampaignStatsCollector;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\YandexDirectService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Mockery;
use Src\Domain\YandexDirect\YandexDirectRepositoryInterface;
use Tests\TestCase;

class YandexDirectCampaignStatsCollectorTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_collect_range_replaces_campaign_rows_for_period(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $data = $this->makeDirectIntegrationData();

        YandexDirectCampaignStats::query()->create([
            'project_id' => $project->id,
            'campaign_name' => 'Старая',
            'campaign_id' => 1,
            'impressions' => 1,
            'clicks' => 1,
            'cost_with_vat' => 1,
            'cost_without_vat' => 1,
            'conversions' => 0,
            'goal_name' => null,
            'date' => '2026-08-01',
        ]);

        $repository = Mockery::mock(IntegrationRepository::class);
        $repository->shouldReceive('getActiveIntegrationsMappedByProjects')
            ->once()
            ->with([$project->id])
            ->andReturn(collect([$project->id => collect([$data])]));

        $direct = Mockery::mock(YandexDirectService::class);
        $direct->shouldReceive('setupClient')->twice()->with('tok', 'login');
        $direct->shouldReceive('getCampaignDailyPerformance')
            ->once()
            ->withArgs(fn ($from, $to, $vat) => $vat === true)
            ->andReturn([
                [
                    'date' => '2026-08-01',
                    'campaign_id' => 10,
                    'campaign_name' => 'Новая',
                    'impressions' => 100,
                    'clicks' => 5,
                    'cost' => 50.0,
                    'conversions' => 2,
                ],
            ]);
        $direct->shouldReceive('getCampaignDailyPerformance')
            ->once()
            ->withArgs(fn ($from, $to, $vat) => $vat === false)
            ->andReturn([
                [
                    'date' => '2026-08-01',
                    'campaign_id' => 10,
                    'campaign_name' => 'Новая',
                    'impressions' => 100,
                    'clicks' => 5,
                    'cost' => 40.0,
                    'conversions' => 2,
                ],
            ]);

        $this->app->instance(YandexDirectService::class, $direct);

        $collector = new YandexDirectCampaignStatsCollector(
            new IntegrationProjectCredentials($repository),
            app(ProjectRepository::class),
            app(YandexDirectRepositoryInterface::class),
        );
        $result = $collector->collectRange(
            $project->id,
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-01'),
        );

        $this->assertTrue($result->ok);
        $this->assertSame(1, YandexDirectCampaignStats::query()->where('project_id', $project->id)->count());
        $this->assertDatabaseHas('yandex_direct_campaign_stats', [
            'project_id' => $project->id,
            'date' => '2026-08-01',
            'campaign_id' => 10,
            'campaign_name' => 'Новая',
            'impressions' => 100,
            'clicks' => 5,
            'cost_with_vat' => 50.00,
            'cost_without_vat' => 40.00,
            'conversions' => 2,
        ]);
    }

    private function makeDirectIntegrationData(): ProjectIntegrationData
    {
        $data = new ProjectIntegrationData;
        $data->integration = IntegrationData::from([
            'id' => 1,
            'name' => 'Яндекс.Директ',
            'code' => 'yandex_direct',
            'category' => IntegrationCategory::MONEY,
            'notification' => null,
        ]);
        $data->settings = ['oauth_token' => 'tok', 'client_login' => 'login'];
        $data->isEnabled = true;

        return $data;
    }
}
