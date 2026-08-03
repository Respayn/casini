<?php

namespace App\Console\Commands;

use App\Models\IntegrationSyncRun;
use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DispatchDueIntegrationSyncsCommand extends Command
{
    protected $signature = 'integrations:dispatch-due-syncs
                            {--now= : ISO datetime override (UTC) for tests}
                            {--force : Start run even outside 00:01 window}
                            {--target-date= : Override target date Y-m-d (with --force)}';

    protected $description = 'Если по timezone агентства 00:01 — поставить ночной съём за вчера в очередь';

    public function handle(IntegrationSyncDispatcher $dispatcher): int
    {
        if ($this->option('force')) {
            $timezone = $dispatcher->resolveAgencyTimezone();
            $nowLocal = Carbon::now($timezone);
            $localDate = $nowLocal->toDateString();
            $targetDate = $this->option('target-date')
                ?: $nowLocal->copy()->subDay()->toDateString();

            if (IntegrationSyncRun::query()
                ->whereDate('local_date', $localDate)
                ->where('timezone', $timezone)
                ->exists()
            ) {
                $this->warn('Run for this local date already exists');

                return self::SUCCESS;
            }

            $run = $dispatcher->startRun($localDate, $timezone, $targetDate);
            $this->info("Started sync run #{$run->id} target={$targetDate}");

            return self::SUCCESS;
        }

        $nowUtc = $this->option('now')
            ? Carbon::parse($this->option('now'), 'UTC')
            : null;

        $run = $dispatcher->dispatchIfDue($nowUtc);

        if ($run === null) {
            $this->line('No sync due');

            return self::SUCCESS;
        }

        $this->info("Started sync run #{$run->id}");

        return self::SUCCESS;
    }
}
