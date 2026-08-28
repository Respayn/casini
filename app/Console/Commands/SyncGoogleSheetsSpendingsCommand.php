<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;

class SyncGoogleSheetsSpendingsCommand extends Command
{
    protected $signature = 'google-sheets:sync-spendings';

    protected $description = 'Sync Google Sheets spendings for enabled client projects';

    public function handle(GoogleSheetsService $googleSheetsService): int
    {
        $result = $googleSheetsService->syncAllEnabledProjects();

        $this->info("Google Sheets sync finished: synced={$result['synced']}, failed={$result['failed']}");

        return self::SUCCESS;
    }
}
