<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Enums\IntegrationCategory;
use App\Models\Project;
use App\Models\YandexDirectDailySpending;
use App\Repositories\IntegrationRepository;
use App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector;
use App\Services\YandexDirectService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class YandexDirectDailySpendCollectorTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_collect_range_upserts_costs_with_and_without_vat(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $data = $this->makeDirectIntegrationData();

        $repository = Mockery::mock(IntegrationRepository::class);
        $repository->shouldReceive('getActiveIntegrationsMappedByProjects')
            ->once()
            ->with([$project->id])
            ->andReturn(collect([$project->id => collect([$data])]));

        $direct = Mockery::mock(YandexDirectService::class);
        $direct->shouldReceive('setupClient')->twice()->with('tok', 'login');
        $direct->shouldReceive('getDailyProjectExpenses')
            ->once()
            ->withArgs(fn ($from, $to, $vat) => $vat === true)
            ->andReturn([
                '2026-08-01' => 100.0,
                '2026-08-02' => 20.5,
            ]);
        $direct->shouldReceive('getDailyProjectExpenses')
            ->once()
            ->withArgs(fn ($from, $to, $vat) => $vat === false)
            ->andReturn([
                '2026-08-01' => 80.0,
                '2026-08-02' => 16.0,
            ]);

        $this->app->instance(YandexDirectService::class, $direct);

        $collector = new YandexDirectDailySpendCollector($repository);
        $result = $collector->collectRange(
            $project->id,
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-02'),
        );

        $this->assertTrue($result->ok);
        $this->assertDatabaseHas('yandex_direct_daily_spendings', [
            'project_id' => $project->id,
            'date' => '2026-08-01',
            'cost_with_vat' => 100.00,
            'cost_without_vat' => 80.00,
        ]);
        $this->assertDatabaseHas('yandex_direct_daily_spendings', [
            'project_id' => $project->id,
            'date' => '2026-08-02',
            'cost_with_vat' => 20.50,
            'cost_without_vat' => 16.00,
        ]);
        $this->assertSame(2, YandexDirectDailySpending::query()->where('project_id', $project->id)->count());
    }

    public function test_collect_single_day_delegates_to_range(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $data = $this->makeDirectIntegrationData();

        $repository = Mockery::mock(IntegrationRepository::class);
        $repository->shouldReceive('getActiveIntegrationsMappedByProjects')
            ->once()
            ->andReturn(collect([$project->id => collect([$data])]));

        $direct = Mockery::mock(YandexDirectService::class);
        $direct->shouldReceive('setupClient')->twice();
        $direct->shouldReceive('getDailyProjectExpenses')->twice()->andReturn(['2026-08-02' => 10.0], ['2026-08-02' => 8.0]);
        $this->app->instance(YandexDirectService::class, $direct);

        $collector = new YandexDirectDailySpendCollector($repository);
        $result = $collector->collect(new IntegrationSyncCollectContext($project->id, Carbon::parse('2026-08-02')));

        $this->assertTrue($result->ok);
        $this->assertDatabaseHas('yandex_direct_daily_spendings', [
            'project_id' => $project->id,
            'date' => '2026-08-02',
            'cost_with_vat' => 10.00,
            'cost_without_vat' => 8.00,
        ]);
    }

    private function makeDirectIntegrationData(): ProjectIntegrationData
    {
        $data = new ProjectIntegrationData();
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
