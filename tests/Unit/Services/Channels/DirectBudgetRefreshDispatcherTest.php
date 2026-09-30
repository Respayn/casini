<?php

namespace Tests\Unit\Services\Channels;

use App\Models\Agency;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Services\Channels\ChannelDirectMetricsService;
use App\Services\Channels\DirectBudgetRefreshDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class DirectBudgetRefreshDispatcherTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_refresh_window_matches_configured_time(): void
    {
        $agency = Agency::query()->orderBy('id')->first();
        if ($agency === null) {
            Agency::factory()->create(['time_zone' => 'Asia/Yekaterinburg', 'direct_budget_refresh_time' => '09:00:00']);
        } else {
            $agency->update(['direct_budget_refresh_time' => '09:00:00']);
        }

        $dispatcher = $this->makeDispatcher();

        $this->assertTrue($dispatcher->isRefreshWindow(Carbon::parse('2026-08-11 09:00:00', 'Asia/Yekaterinburg')));
        $this->assertFalse($dispatcher->isRefreshWindow(Carbon::parse('2026-08-11 09:01:00', 'Asia/Yekaterinburg')));
        $this->assertFalse($dispatcher->isRefreshWindow(Carbon::parse('2026-08-11 08:59:00', 'Asia/Yekaterinburg')));
    }

    public function test_dispatch_if_due_runs_only_once_per_local_date(): void
    {
        $agency = Agency::query()->orderBy('id')->first();
        if ($agency === null) {
            Agency::factory()->create(['time_zone' => 'Asia/Yekaterinburg', 'direct_budget_refresh_time' => '09:00:00']);
        } else {
            $agency->update(['time_zone' => 'Asia/Yekaterinburg', 'direct_budget_refresh_time' => '09:00:00']);
        }

        Cache::forget('channels.direct.budget.scheduled.2026-08-11');

        $metricsService = Mockery::mock(ChannelDirectMetricsService::class);
        $metricsService->shouldReceive('refreshBudgetsForcedWithoutThrottle')->once();

        $dispatcher = new DirectBudgetRefreshDispatcher($metricsService);

        $this->createProjectWithDirect();

        $nowUtc = Carbon::parse('2026-08-11 04:00:00', 'UTC');

        $first = $dispatcher->dispatchIfDue($nowUtc);
        $second = $dispatcher->dispatchIfDue($nowUtc);

        $this->assertTrue($first);
        $this->assertFalse($second);
    }

    public function test_dispatch_if_due_respects_timezone(): void
    {
        $agency = Agency::query()->orderBy('id')->first();
        if ($agency === null) {
            Agency::factory()->create(['time_zone' => 'Europe/Moscow', 'direct_budget_refresh_time' => '10:00:00']);
        } else {
            $agency->update(['time_zone' => 'Europe/Moscow', 'direct_budget_refresh_time' => '10:00:00']);
        }

        Cache::forget('channels.direct.budget.scheduled.2026-08-11');

        $metricsService = Mockery::mock(ChannelDirectMetricsService::class);
        $metricsService->shouldReceive('refreshBudgetsForcedWithoutThrottle')->once();

        $dispatcher = new DirectBudgetRefreshDispatcher($metricsService);

        $this->createProjectWithDirect();

        $nowUtc = Carbon::parse('2026-08-11 07:00:00', 'UTC');
        $result = $dispatcher->dispatchIfDue($nowUtc);

        $this->assertTrue($result);
    }

    private function makeDispatcher(): DirectBudgetRefreshDispatcher
    {
        $metricsService = Mockery::mock(ChannelDirectMetricsService::class);
        $metricsService->shouldReceive('refreshBudgetsForcedWithoutThrottle')->zeroOrMoreTimes();

        return new DirectBudgetRefreshDispatcher($metricsService);
    }

    private function createProjectWithDirect(): Project
    {
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();
        $project = Project::factory()->create(['is_active' => true]);

        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => ['oauth_token' => 'test', 'client_login' => 'test_login'],
        ]);

        return $project;
    }
}
