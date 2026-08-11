<?php

namespace App\Console\Commands;

use App\Services\Channels\DirectBudgetRefreshDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DispatchDueDirectBudgetRefreshCommand extends Command
{
    protected $signature = 'channels:dispatch-due-budget-refresh
                            {--now= : ISO datetime override (UTC) for tests}
                            {--force : Run refresh regardless of time window}';

    protected $description = 'Обновить остаток бюджета Директа, если наступило настроенное время (по timezone агентства)';

    public function handle(DirectBudgetRefreshDispatcher $dispatcher): int
    {
        if ($this->option('force')) {
            $projectIds = \App\Models\Project::query()
                ->where('is_active', true)
                ->whereHas('integrations', fn ($q) => $q->where('code', 'yandex_direct'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($projectIds === []) {
                $this->info('No projects with Yandex Direct found');

                return self::SUCCESS;
            }

            app(\App\Services\Channels\ChannelDirectMetricsService::class)
                ->refreshBudgetsForcedWithoutThrottle($projectIds);

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
