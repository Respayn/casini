<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\YandexMetrikaVisitsGeo;
use App\Services\YandexMetrikaService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaGeoVisitsSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_command_writes_visits_rows_for_enabled_report(): void
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
                'visits_metric' => 'visits',
                'reports' => [
                    'visits_geo' => true,
                ],
            ],
        ]);

        $this->mock(YandexMetrikaService::class, function ($mock) {
            $mock->shouldReceive('setupClientFromSettings')->atLeast()->once();
            $mock->shouldReceive('fetchGeoVisitsStats')->atLeast()->once()->andReturn([
                [
                    'city' => 'Москва',
                    'month' => '2026-08-01',
                    'visits' => 42,
                    'visitors' => 0,
                    'value' => 42,
                ],
            ]);
        });

        $this->artisan('metrika:sync-geo-visits')->assertSuccessful();

        $row = YandexMetrikaVisitsGeo::query()
            ->where('project_id', $project->id)
            ->where('city', 'Москва')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(42, $row->visits);
        $this->assertSame(0, $row->visitors);
        $this->assertSame(0, $row->goal_reaches);
    }

    #[Test]
    public function test_command_skips_projects_without_geo_report(): void
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
                'reports' => [
                    'visits_search_engines' => true,
                ],
            ],
        ]);

        $this->artisan('metrika:sync-geo-visits')->assertSuccessful();

        $this->assertSame(0, YandexMetrikaVisitsGeo::query()
            ->where('project_id', $project->id)
            ->count());
    }
}
