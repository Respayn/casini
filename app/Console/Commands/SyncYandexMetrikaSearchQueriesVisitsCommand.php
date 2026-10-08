<?php

namespace App\Console\Commands;

use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use Illuminate\Console\Command;

/**
 * Ручной съём с месяца включения по сегодня. Ночью отчёт снимает collector yandex_metrika.
 */
class SyncYandexMetrikaSearchQueriesVisitsCommand extends Command
{
    protected $signature = 'metrika:sync-search-queries-visits';

    protected $description = 'Съём переходов из отчёта «Поисковые запросы» Яндекс Метрики';

    public function handle(YandexMetrikaReportsSync $reportsSync): int
    {
        $stats = $reportsSync->syncReportForAllProjects(YandexMetrikaReportsSync::VISITS_SEARCH_QUERIES);

        foreach ($stats['errors'] as $projectId => $message) {
            $this->error('Проект '.$projectId.': '.$message);
        }

        $this->info("Синхронизировано: {$stats['synced']}, пропущено: {$stats['skipped']}, ошибок: {$stats['failed']}");

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
