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
    public function test_command_writes_utm_rows_for_enabled_report(): void
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
                ['utm_dimension' => 'ym:s:UTMSource', 'utm_value' => 'yandex', 'date' => '2026-08-15', 'value' => 3],
            ]);
        });

        $this->artisan('metrika:sync-utm-goals')->assertSuccessful();

        $this->assertSame(3, YandexMetrikaGoalUtm::query()
            ->where('project_id', $project->id)
            ->count());

        $row = YandexMetrikaGoalUtm::query()
            ->where('project_id', $project->id)
            ->first();

        $this->assertSame('yandex', $row->utm_source);
        $this->assertSame('2026-08-15', $row->achieved_date->format('Y-m-d'));
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

        $this->artisan('metrika:sync-utm-goals')->assertSuccessful();

        $this->assertSame(0, YandexMetrikaGoalUtm::query()
            ->where('project_id', $project->id)
            ->count());
    }
}
