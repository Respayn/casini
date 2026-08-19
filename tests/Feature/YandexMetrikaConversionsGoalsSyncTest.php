<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\YandexMetrikaGoalConversion;
use App\Services\YandexMetrikaService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaConversionsGoalsSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_command_writes_conversions_rows_for_enabled_report(): void
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
                    'goals_conversions' => true,
                ],
            ],
        ]);

        $this->mock(YandexMetrikaService::class, function ($mock) {
            $mock->shouldReceive('setupClientFromSettings')->once();
            $mock->shouldReceive('fetchConversionsGoalsStats')->once()->andReturn([
                ['goal_name' => 'Заявка', 'month' => '2026-08-01', 'value' => 15],
            ]);
        });

        $this->artisan('metrika:sync-conversions-goals')->assertSuccessful();

        $this->assertSame(1, YandexMetrikaGoalConversion::query()
            ->where('project_id', $project->id)
            ->count());

        $row = YandexMetrikaGoalConversion::query()
            ->where('project_id', $project->id)
            ->first();

        $this->assertSame('Заявка', $row->goal_name);
        $this->assertSame(15, $row->conversions);
    }

    #[Test]
    public function test_command_skips_projects_without_conversions_report(): void
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

        $this->artisan('metrika:sync-conversions-goals')->assertSuccessful();

        $this->assertSame(0, YandexMetrikaGoalConversion::query()
            ->where('project_id', $project->id)
            ->count());
    }
}
