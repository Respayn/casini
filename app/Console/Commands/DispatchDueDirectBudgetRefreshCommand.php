<?php

namespace App\Console\Commands;

use App\Repositories\ProjectRepository;
use App\Services\Channels\ChannelDirectMetricsService;
use App\Services\Channels\DirectBudgetRefreshDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DispatchDueDirectBudgetRefreshCommand extends Command
{
    protected $signature = 'channels:dispatch-due-budget-refresh
                            {--now= : ISO datetime override (UTC) for tests}
                            {--force : Run refresh regardless of time window}';

    protected $description = 'Обновить остаток бюджета Директа, если наступило настроенное время (по timezone агентства)';

    public function handle(
        DirectBudgetRefreshDispatcher $dispatcher,
        ProjectRepository $projectRepository,
        ChannelDirectMetricsService $directMetricsService,
    ): int {
        if ($this->option('force')) {
            $projectIds = $projectRepository->getActiveProjectIdsWithIntegration('yandex_direct');

            if ($projectIds === []) {
                $this->info('No projects with Yandex Direct found');

                return self::SUCCESS;
            }

            $directMetricsService->refreshBudgetsForcedWithoutThrottle($projectIds);

            $this->info(sprintf('Forced refresh for %d projects', count($projectIds)));

            return self::SUCCESS;
        }

        $nowUtc = $this->option('now')
            ? Carbon::parse($this->option('now'), 'UTC')
            : null;

        $dispatched = $dispatcher->dispatchIfDue($nowUtc);

        $this->line($dispatched ? 'Budget refresh dispatched' : 'Not in refresh window');

        return self::SUCCESS;
    }
}
