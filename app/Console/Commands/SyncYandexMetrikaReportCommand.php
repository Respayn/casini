<?php

namespace App\Console\Commands;

use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use Illuminate\Console\Command;

/**
 * Ручной съём отчёта с месяца включения по сегодня. Ночью отчёты снимает collector yandex_metrika.
 */
class SyncYandexMetrikaReportCommand extends Command
{
    protected $signature = 'metrika:sync {report : Ключ отчёта из YandexMetrikaReportsSync::REPORTS}';

    protected $description = 'Съём отчёта Яндекс Метрики по всем проектам';

    public function handle(YandexMetrikaReportsSync $reportsSync): int
    {
        $report = (string) $this->argument('report');

        if (! in_array($report, YandexMetrikaReportsSync::REPORTS, true)) {
            $this->error('Неизвестный отчёт: '.$report.'. Допустимые: '.implode(', ', YandexMetrikaReportsSync::REPORTS));

            return self::INVALID;
        }

        $stats = $reportsSync->syncReportForAllProjects($report);

        foreach ($stats['errors'] as $projectId => $message) {
            $this->error('Проект '.$projectId.': '.$message);
        }

        $this->info("Синхронизировано: {$stats['synced']}, пропущено: {$stats['skipped']}, ошибок: {$stats['failed']}");

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
