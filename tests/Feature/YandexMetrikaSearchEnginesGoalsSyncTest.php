<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\YandexMetrikaSearchEngineGoal;
use App\Models\YandexMetrikaSearchEnginesStats;
use App\Repositories\AgencyRepository;
use App\Repositories\IntegrationRepository;
use App\Services\IntegrationSync\YandexMetrikaSyncPeriod;
use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use App\Services\YandexMetrikaService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\EloquentYandexMetrikaRepository;
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
    public function test_sync_report_writes_selected_goals_including_zeros(): void
    {
        $project = Project::factory()->create();
        $metrikaService = $this->createMock(YandexMetrikaService::class);
        $metrikaService->expects($this->once())->method('setupClientFromSettings');
        $metrikaService->expects($this->once())
            ->method('fetchSearchEnginesGoalsStats')
            ->willReturn([
                ['search_engine' => 'yandex', 'month' => '2026-08-01', 'goal_id' => 111, 'value' => 8],
                ['search_engine' => 'yandex', 'month' => '2026-08-01', 'goal_id' => 222, 'value' => 0],
            ]);
        $metrikaService->expects($this->once())
            ->method('listGoalOptions')
            ->willReturn([
                ['id' => 111, 'name' => 'Заявка'],
                ['id' => 222, 'name' => 'Звонок'],
            ]);

        $sync = new YandexMetrikaReportsSync(
            $metrikaService,
            app(EloquentYandexMetrikaRepository::class),
            $this->createStub(IntegrationRepository::class),
            app(AgencyRepository::class),
            new YandexMetrikaSyncPeriod,
        );

        $sync->syncReport(
            $project->id,
            [
                'oauth_token' => 'token',
                'oauth_yandex_login' => 'login',
                'counter_id' => 12345678,
                'goals' => [111, 222],
                'goals_metric' => 'target_visits',
                'reports' => ['goals_search_engines' => true],
            ],
            YandexMetrikaReportsSync::GOALS_SEARCH_ENGINES,
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-18'),
        );

        $this->assertSame(8, YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->value('conversions'));
        $this->assertSame(0, YandexMetrikaSearchEnginesStats::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->value('visits'));

        $this->assertSame(8, YandexMetrikaSearchEngineGoal::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->where('goal_id', 111)
            ->value('conversions'));
        $this->assertSame(0, YandexMetrikaSearchEngineGoal::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->where('goal_id', 222)
            ->value('conversions'));
        $this->assertSame(0, YandexMetrikaSearchEngineGoal::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'google')
            ->where('goal_id', 111)
            ->value('conversions'));
        $this->assertSame('Заявка', YandexMetrikaSearchEngineGoal::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->where('goal_id', 111)
            ->value('goal_name'));
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
                ['search_engine' => 'yandex', 'month' => '2026-08-01', 'goal_id' => 111, 'value' => 8],
                ['search_engine' => 'google', 'month' => '2026-08-01', 'goal_id' => 111, 'value' => 3],
            ]);
            $mock->shouldReceive('listGoalOptions')->once()->andReturn([
                ['id' => 111, 'name' => 'Заявка'],
            ]);
        });

        $this->artisan('metrika:sync', ['report' => 'goals_search_engines'])->assertSuccessful();

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

        $yandexGoal = YandexMetrikaSearchEngineGoal::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'yandex')
            ->where('goal_id', 111)
            ->whereDate('month', '2026-08-01')
            ->first();
        $googleGoal = YandexMetrikaSearchEngineGoal::query()
            ->where('project_id', $project->id)
            ->where('search_engine', 'google')
            ->where('goal_id', 111)
            ->whereDate('month', '2026-08-01')
            ->first();

        $this->assertSame('Заявка', $yandexGoal?->goal_name);
        $this->assertSame(8, $yandexGoal?->conversions);
        $this->assertSame(3, $googleGoal?->conversions);
    }
}
