<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\YandexMetrikaSearchEnginesStats;
use App\Services\YandexMetrikaService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaSearchEnginesVisitsSyncTest extends TestCase
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
                'search_engines_all' => false,
                'search_engines' => ['yandex'],
                'reports' => [
                    'visits_search_engines' => true,
                ],
            ],
        ]);

        $this->mock(YandexMetrikaService::class, function ($mock) {
            $mock->shouldReceive('setupClientFromSettings')->atLeast()->once();
            $mock->shouldReceive('fetchSearchEnginesVisitsStats')->atLeast()->once()->andReturn([
                ['search_engine' => 'yandex', 'search_engine_label' => 'Яндекс', 'month' => '2026-08-01', 'value' => 42],
            ]);
        });

        $this->artisan('metrika:sync-search-engines-visits')->assertSuccessful();

        $this->assertSame(1, YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->count());

        $yandex = YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->first();

        $this->assertSame(42, $yandex->visits);
        $this->assertSame(0, $yandex->conversions);
    }

    #[Test]
    public function test_command_skips_projects_without_visits_report(): void
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
                    'goals_search_engines' => true,
                ],
            ],
        ]);

        $this->artisan('metrika:sync-search-engines-visits')->assertSuccessful();

        $this->assertSame(0, YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->count());
    }
}
