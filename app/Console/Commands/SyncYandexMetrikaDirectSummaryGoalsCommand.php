<?php

namespace App\Console\Commands;

use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use Illuminate\Console\Command;

/**
 * Ручной съём с месяца включения по сегодня. Ночью отчёт снимает collector yandex_metrika.
 */
class SyncYandexMetrikaDirectSummaryGoalsCommand extends Command
{
    protected $signature = 'metrika:sync-direct-summary-goals';

    protected $description = 'Съём достижений целей из отчёта «Директ, сводка» Яндекс Метрики';

    public function handle(YandexMetrikaReportsSync $reportsSync): int
    {
        $stats = $reportsSync->syncReportForAllProjects(YandexMetrikaReportsSync::GOALS_DIRECT_SUMMARY);

        foreach ($stats['errors'] as $projectId => $message) {
            $this->error('Проект '.$projectId.': '.$message);
        }

        $this->info("Синхронизировано: {$stats['synced']}, пропущено: {$stats['skipped']}, ошибок: {$stats['failed']}");

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
