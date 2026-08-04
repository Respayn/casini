<?php

namespace Tests\Unit\Services\Channels;

use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Enums\IntegrationCategory;
use App\Models\Project;
use App\Models\User;
use App\Models\YandexDirectDailySpending;
use App\Repositories\IntegrationRepository;
use App\Services\Channels\ChannelDirectApiThrottle;
use App\Services\Channels\ChannelDirectMetricsService;
use App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector;
use App\Services\YandexDirectService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ChannelDirectMetricsServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_resolve_month_period_clips_to_today_for_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));

        $service = $this->makeService();
        [$from, $to] = $service->resolveMonthPeriod(Carbon::parse('2026-08-01'));

        $this->assertSame('2026-08-01', $from->toDateString());
        $this->assertSame('2026-08-03', $to->toDateString());

        Carbon::setTestNow();
    }

    public function test_resolve_period_spans_multiple_months_and_clips_end(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));

        $service = $this->makeService();
        [$from, $to] = $service->resolvePeriod(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-08-01'),
        );

        $this->assertSame('2026-07-01', $from->toDateString());
        $this->assertSame('2026-08-03', $to->toDateString());

        Carbon::setTestNow();
    }

    public function test_budget_cell_params_read_cache_and_credentials_flag(): void
    {
        Cache::put('channels.direct.budget.15', [
            'value' => 1234.5,
            'updated_at' => '2026-08-04T14:30:00+05:00',
        ], 60);

        $integrations = collect([$this->makeDirectIntegration()]);
        $params = $this->makeService()->budgetCellParams(15, $integrations);

        $this->assertSame(1234.5, $params['value']);
        $this->assertNotNull($params['updatedAt']);
        $this->assertTrue($params['updatedAt']->equalTo(Carbon::parse('2026-08-04T14:30:00+05:00')));
        $this->assertSame(15, $params['projectId']);
        $this->assertTrue($params['canRefresh']);
    }

    public function test_budget_cell_params_support_legacy_numeric_cache(): void
    {
        Cache::put('channels.direct.budget.15', 1234.5, 60);

        $params = $this->makeService()->budgetCellParams(15, collect([$this->makeDirectIntegration()]));

        $this->assertSame(1234.5, $params['value']);
        $this->assertNull($params['updatedAt']);
    }

    public function test_get_stored_spendings_sums_days_from_database(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        YandexDirectDailySpending::query()->create([
            'project_id' => $project->id,
            'date' => '2026-07-01',
            'cost_with_vat' => 100.50,
            'cost_without_vat' => 80.00,
        ]);
        YandexDirectDailySpending::query()->create([
            'project_id' => $project->id,
            'date' => '2026-07-02',
            'cost_with_vat' => 50.25,
            'cost_without_vat' => 40.00,
        ]);
        YandexDirectDailySpending::query()->create([
            'project_id' => $project->id,
            'date' => '2026-08-01',
            'cost_with_vat' => 10.00,
            'cost_without_vat' => 8.00,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-31'));

        $sumMonth = $this->makeService()->getStoredSpendings(
            $project->id,
            Carbon::parse('2026-07-15'),
            Carbon::parse('2026-07-15'),
            true,
        );
        $sumRange = $this->makeService()->getStoredSpendings(
            $project->id,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-08-01'),
            true,
        );
        $sumNoVat = $this->makeService()->getStoredSpendings(
            $project->id,
            Carbon::parse('2026-07-15'),
            Carbon::parse('2026-07-15'),
            false,
        );

        $this->assertSame(150.75, $sumMonth);
        $this->assertSame(160.75, $sumRange);
        $this->assertSame(120.0, $sumNoVat);

        Carbon::setTestNow();
    }

    public function test_get_stored_spendings_returns_null_when_no_rows(): void
    {
        $this->assertNull(
            $this->makeService()->getStoredSpendings(
                999999,
                Carbon::parse('2026-07-01'),
                Carbon::parse('2026-07-01'),
                true,
            )
        );
    }

    public function test_refresh_budget_skips_without_credentials(): void
    {
        $repository = Mockery::mock(IntegrationRepository::class);
        $repository->shouldReceive('getActiveIntegrationsMappedByProjects')
            ->once()
            ->with([42])
            ->andReturn(collect([42 => collect()]));

        $service = new ChannelDirectMetricsService(
            $repository,
            Mockery::mock(YandexDirectDailySpendCollector::class),
            new ChannelDirectApiThrottle(),
        );

        $result = $service->refreshBudget(42);

        $this->assertFalse($result['ok']);
        $this->assertSame('Нет настроенной интеграции Яндекс.Директ', $result['error']);
    }

    public function test_refresh_budget_stores_value_in_cache(): void
    {
        $this->actingAs(User::factory()->create());

        $repository = Mockery::mock(IntegrationRepository::class);
        $repository->shouldReceive('getActiveIntegrationsMappedByProjects')
            ->once()
            ->with([7])
            ->andReturn(collect([7 => collect([$this->makeDirectIntegration()])]));

        $direct = Mockery::mock(YandexDirectService::class);
        $direct->shouldReceive('setupClient')->once()->with('token-1', 'client-login');
        $direct->shouldReceive('getAccountBalance')->once()->andReturn(1500.456);

        $this->app->instance(YandexDirectService::class, $direct);

        $service = new ChannelDirectMetricsService(
            $repository,
            Mockery::mock(YandexDirectDailySpendCollector::class),
            new ChannelDirectApiThrottle(),
        );
        $result = $service->refreshBudget(7);

        $this->assertTrue($result['ok']);
        $this->assertSame(1500.46, $result['value']);
        $cached = Cache::get('channels.direct.budget.7');
        $this->assertIsArray($cached);
        $this->assertSame(1500.46, $cached['value']);
        $this->assertNotEmpty($cached['updated_at']);
    }

    public function test_refresh_budget_returns_cache_without_api_on_second_click(): void
    {
        Cache::put('channels.direct.budget.7', [
            'value' => 100.0,
            'updated_at' => '2026-08-04T10:00:00+00:00',
        ], 60);
        $repository = Mockery::mock(IntegrationRepository::class);
        $repository->shouldReceive('getActiveIntegrationsMappedByProjects')
            ->once()
            ->with([7])
            ->andReturn(collect([7 => collect([$this->makeDirectIntegration()])]));

        $service = new ChannelDirectMetricsService(
            $repository,
            Mockery::mock(YandexDirectDailySpendCollector::class),
            new ChannelDirectApiThrottle(),
        );

        $result = $service->refreshBudget(7, force: false);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['fromCache'] ?? false);
        $this->assertSame(100.0, $result['value']);
    }

    private function makeService(): ChannelDirectMetricsService
    {
        return new ChannelDirectMetricsService(
            Mockery::mock(IntegrationRepository::class),
            Mockery::mock(YandexDirectDailySpendCollector::class),
            new ChannelDirectApiThrottle(),
        );
    }

    private function makeDirectIntegration(): ProjectIntegrationData
    {
        $data = new ProjectIntegrationData();
        $data->integration = IntegrationData::from([
            'id' => 1,
            'name' => 'Яндекс.Директ',
            'code' => 'yandex_direct',
            'category' => IntegrationCategory::MONEY,
            'notification' => null,
        ]);
        $data->settings = [
            'oauth_token' => 'token-1',
            'client_login' => 'client-login',
        ];
        $data->isEnabled = true;

        return $data;
    }
}
