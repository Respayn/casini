<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\YandexMetrikaGoalDirectSummary;
use App\Services\YandexMetrikaService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaDirectSummaryGoalsSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_command_writes_rows_for_enabled_report(): void
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
                'reports' => [
                    'goals_direct_summary' => true,
                ],
            ],
        ]);

        // На staging в той же БД могут быть другие проекты с Метрикой:
        // команда обходит все, поэтому ожидаем «хотя бы один» вызов.
        $this->mock(YandexMetrikaService::class, function ($mock) {
            $mock->shouldReceive('setupClientFromSettings')->atLeast()->once();
            $mock->shouldReceive('fetchDirectSummaryGoalsStats')->atLeast()->once()->andReturn([
                ['goal_name' => 'Заявка', 'month' => '2026-08-01', 'value' => 15],
            ]);
            $mock->shouldReceive('fetchDirectSummaryGoalsByCampaignStats')->atLeast()->once()->andReturn([
                [
                    'goal_name' => 'Заявка',
                    'campaign_id' => 100,
                    'campaign_name' => 'Кампания А',
                    'month' => '2026-08-01',
                    'value' => 15,
                ],
            ]);
        });

        $this->artisan('metrika:sync', ['report' => 'goals_direct_summary'])->assertSuccessful();

        $this->assertSame(1, YandexMetrikaGoalDirectSummary::query()
            ->where('project_id', $project->id)
            ->count());

        $row = YandexMetrikaGoalDirectSummary::query()
            ->where('project_id', $project->id)
            ->first();

        $this->assertSame('Заявка', $row->goal_name);
        $this->assertSame(15, $row->conversions);

        $this->assertDatabaseHas('yandex_metrika_goal_direct_campaigns', [
            'project_id' => $project->id,
            'goal_name' => 'Заявка',
            'campaign_id' => 100,
            'campaign_name' => 'Кампания А',
            'conversions' => 15,
        ]);
    }

    #[Test]
    public function test_command_skips_projects_without_direct_summary_report(): void
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

        $this->artisan('metrika:sync', ['report' => 'goals_direct_summary'])->assertSuccessful();

        $this->assertSame(0, YandexMetrikaGoalDirectSummary::query()
            ->where('project_id', $project->id)
            ->count());
    }
}
