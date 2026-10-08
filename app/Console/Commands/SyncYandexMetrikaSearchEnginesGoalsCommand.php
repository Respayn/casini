<?php

namespace App\Console\Commands;

use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use Illuminate\Console\Command;

/**
 * Ручной съём с месяца включения по сегодня. Ночью отчёт снимает collector yandex_metrika.
 */
class SyncYandexMetrikaSearchEnginesGoalsCommand extends Command
{
    protected $signature = 'metrika:sync-search-engines-goals';

    protected $description = 'Съём достижений целей из отчёта «Поисковые системы» Яндекс Метрики';

    public function handle(YandexMetrikaReportsSync $reportsSync): int
    {
        $stats = $reportsSync->syncReportForAllProjects(YandexMetrikaReportsSync::GOALS_SEARCH_ENGINES);

        foreach ($stats['errors'] as $projectId => $message) {
            $this->error('Проект '.$projectId.': '.$message);
        }

        $this->info("Синхронизировано: {$stats['synced']}, пропущено: {$stats['skipped']}, ошибок: {$stats['failed']}");

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
