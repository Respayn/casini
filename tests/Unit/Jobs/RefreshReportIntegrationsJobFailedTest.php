<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RefreshReportIntegrationsJob;
use App\Services\IntegrationSync\IntegrationManualRefreshNotifier;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\IntegrationSync\IntegrationManualRefreshTimestamp;
use App\Services\Statistics\StatisticsService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RefreshReportIntegrationsJobFailedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_failed_marks_project_and_closes_run_when_last_pending(): void
    {
        $progress = app(IntegrationManualRefreshProgress::class);
        $state = $progress->start(9, 'channels', [
            ['id' => 30, 'label' => 'mmk-metiz.ru', 'group' => null],
        ], '2026-09-01', '2026-09-01', false);

        $progress->setCurrentStep(9, 30, 'bitrix24_labor', 1);

        $this->app->instance(
            IntegrationManualRefreshTimestamp::class,
            Mockery::mock(IntegrationManualRefreshTimestamp::class, function ($mock) {
                $mock->shouldReceive('record')->once()->with(9);
            })
        );
        $this->app->instance(
            IntegrationManualRefreshNotifier::class,
            Mockery::mock(IntegrationManualRefreshNotifier::class, function ($mock) {
                $mock->shouldReceive('notifyAgency')->once();
            })
        );
        $this->app->instance(
            StatisticsService::class,
            Mockery::mock(StatisticsService::class)
        );

        $job = new RefreshReportIntegrationsJob(
            userId: 1,
            agencyId: 9,
            product: 'channels',
            projectId: 30,
            projectIndex: 1,
            periodFrom: '2026-09-01',
            periodTo: '2026-09-01',
            includeVat: false,
            withDirectBudget: false,
            recalculateBonuses: false,
            runId: $state['run_id'],
        );

        $job->failed(new RuntimeException('queue boom'));

        $after = $progress->get(9);
        $this->assertNotNull($after);
        $this->assertSame(IntegrationManualRefreshProgress::STATUS_DONE, $after['status']);
        $this->assertSame([], $after['pending']);
        $this->assertSame(1, $after['failed']);
        $this->assertFalse($progress->isRunning(9));
    }

    public function test_failed_ignores_stale_run_id(): void
    {
        $progress = app(IntegrationManualRefreshProgress::class);
        $progress->start(9, 'channels', [
            ['id' => 30, 'label' => 'mmk-metiz.ru', 'group' => null],
        ], '2026-09-01', '2026-09-01', false);

        $job = new RefreshReportIntegrationsJob(
            userId: 1,
            agencyId: 9,
            product: 'channels',
            projectId: 30,
            projectIndex: 1,
            periodFrom: '2026-09-01',
            periodTo: '2026-09-01',
            includeVat: false,
            withDirectBudget: false,
            recalculateBonuses: false,
            runId: 'stale-run',
        );

        $job->failed(new RuntimeException('queue boom'));

        $this->assertTrue($progress->isRunning(9));
        $this->assertCount(1, $progress->get(9)['pending'] ?? []);
    }
}
