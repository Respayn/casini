<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;

class SyncGoogleSheetsSpendingsCommand extends Command
{
    protected $signature = 'google-sheets:sync-spendings';

    protected $description = 'Ночной съём расходов Google Таблиц за текущий открытый месяц';

    public function handle(GoogleSheetsService $googleSheetsService): int
    {
        $result = $googleSheetsService->syncOpenMonthForAllEnabledProjects();

        $this->info(sprintf(
            'Google Sheets nightly sync: synced=%d, skipped=%d, failed=%d',
            $result['synced'],
            $result['skipped'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
