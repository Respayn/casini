<?php

namespace App\Console\Commands;

use App\Services\IntegrationSync\IntegrationSyncDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BackfillIntegrationDataCommand extends Command
{
    protected $signature = 'integrations:backfill
                            {--project= : ID клиенто-проекта (обязательно)}
                            {--from= : Дата начала Y-m-d (обязательно)}
                            {--to= : Дата конца Y-m-d (обязательно)}
                            {--collector= : Ключ коллектора (yandex_direct_daily_spend|callibri_daily_leads|yandex_search_api_daily_positions); без опции — все подходящие}';

    protected $description = 'Догрузить данные интеграций за период (без ночного run и без throttle UI)';

    public function handle(IntegrationSyncDispatcher $dispatcher): int
    {
        $projectOption = $this->option('project');
        $fromOption = $this->option('from');
        $toOption = $this->option('to');
        $collectorKey = $this->option('collector');

        if (! filled($projectOption) || ! filled($fromOption) || ! filled($toOption)) {
            $this->error('Нужны опции --project, --from и --to');

            return self::FAILURE;
        }

        if (! ctype_digit((string) $projectOption)) {
            $this->error('--project должен быть целым числом');

            return self::FAILURE;
        }

        $projectId = (int) $projectOption;

        try {
            $from = Carbon::createFromFormat('Y-m-d', (string) $fromOption)->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', (string) $toOption)->startOfDay();
        } catch (\Throwable) {
            $this->error('--from и --to должны быть в формате Y-m-d');

            return self::FAILURE;
        }

        if ($from->greaterThan($to)) {
            $this->error('--from не может быть позже --to');

            return self::FAILURE;
        }

        $collectors = $dispatcher->collectors();

        if (filled($collectorKey)) {
            $collector = $dispatcher->collector((string) $collectorKey);

            if ($collector === null) {
                $this->error('Неизвестный collector: '.$collectorKey);

                return self::FAILURE;
            }

            $collectors = [$collector];
        }

        $ran = 0;

        foreach ($collectors as $collector) {
            if (! $collector->supportsProject($projectId)) {
                $this->line('skip '.$collector->key().' (нет интеграции / credentials)');

                continue;
            }

            $result = $collector->collectRange($projectId, $from, $to);

            if (! $result->ok) {
                $this->error($collector->key().': '.($result->error ?? 'Съём не удался'));

                return self::FAILURE;
            }

            $this->info(sprintf(
                'OK: collector=%s project=%d from=%s to=%s',
                $collector->key(),
                $projectId,
                $from->toDateString(),
                $to->toDateString(),
            ));
            $ran++;
        }

        if ($ran === 0) {
            $this->warn('Нет подходящих collectors для проекта');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
