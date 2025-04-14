<?php

namespace App\Console\Commands;

use App\Services\YandexDirectService;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'app:test';

    protected $description = 'Команда для вариативного тестирования методов';

    public function __construct(
        protected YandexDirectService $service)
    {
        parent::__construct();
    }

    public function handle()
    {
        dd($this->service);
        // Пример использования сервисного класса
        $dateFrom = '2023-01-01';
        $dateTo = '2023-12-31';

        $expenses = $this->service->getExpenses($dateFrom, $dateTo);
        $this->info('Expenses:');
        print_r($expenses);

        $balance = $this->service->getAccountBalance();
        $this->info('Account Balance: ' . $balance);

        $reportData = $this->service->getReportData($dateFrom, $dateTo);
        $this->info('Report Data:');
        print_r($reportData);
    }
}
