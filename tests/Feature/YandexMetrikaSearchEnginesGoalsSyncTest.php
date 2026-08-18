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
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Tests\TestCase;

class YandexMetrikaSearchEnginesGoalsSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_upsert_does_not_overwrite_visits(): void
    {
        $project = Project::factory()->create();

        YandexMetrikaSearchEnginesStats::query()->create([
            'project_id' => $project->id,
            'search_engine' => 'yandex',
            'month' => '2026-08-01',
            'visits' => 500,
            'conversions' => 1,
        ]);

        app(YandexMetrikaRepositoryInterface::class)->upsertSearchEnginesConversions(
            $project->id,
            'yandex',
            '2026-08-01',
            12
        );

        $row = YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->first();

        $this->assertSame(500, $row->visits);
        $this->assertSame(12, $row->conversions);
    }

    #[Test]
    public function test_command_writes_conversions_for_enabled_report(): void
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
                    'goals_search_engines' => true,
                ],
            ],
        ]);

        $this->mock(YandexMetrikaService::class, function ($mock) {
            $mock->shouldReceive('setupClientFromSettings')->once();
            $mock->shouldReceive('fetchSearchEnginesGoalsStats')->once()->andReturn([
                ['search_engine' => 'yandex', 'month' => '2026-08-01', 'value' => 8],
                ['search_engine' => 'google', 'month' => '2026-08-01', 'value' => 3],
            ]);
        });

        $this->artisan('metrika:sync-search-engines-goals')->assertSuccessful();

        $this->assertSame(8, YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->value('conversions'));
        $this->assertSame(3, YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'google')
            ->value('conversions'));
        $this->assertSame(0, YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->value('visits'));
    }
}
