<?php

namespace App\Console\Commands;

use App\Services\IntegrationSync\Collectors\YandexDirectDailySpendCollector;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * @deprecated Используйте integrations:backfill --collector=yandex_direct_daily_spend
 */
class BackfillDirectSpendCommand extends Command
{
    protected $signature = 'integrations:backfill-direct-spend
                            {--project= : ID клиенто-проекта (обязательно)}
                            {--from= : Дата начала Y-m-d (обязательно)}
                            {--to= : Дата конца Y-m-d (обязательно)}';

    protected $description = 'Догрузить дневной расход Яндекс.Директа за период (alias для integrations:backfill)';

    public function handle(YandexDirectDailySpendCollector $collector): int
    {
        $projectOption = $this->option('project');
        $fromOption = $this->option('from');
        $toOption = $this->option('to');

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

        $result = $collector->collectRange($projectId, $from, $to);

        if (! $result->ok) {
            $this->error($result->error ?? 'Съём не удался');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'OK: project=%d from=%s to=%s',
            $projectId,
            $from->toDateString(),
            $to->toDateString(),
        ));

        return self::SUCCESS;
    }
}
