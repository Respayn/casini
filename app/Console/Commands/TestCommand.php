<?php

namespace App\Console\Commands;

use App\Exceptions\YandexDirectApiException;
use App\Services\YandexDirectService;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'app:test';

    protected $description = 'Команда для вариативного тестирования методов';

    protected YandexDirectService $service;

    public function __construct(YandexDirectService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        try {
            $directService = app(YandexDirectService::class);

            // Получение списка кампаний
//            $campaigns = $directService->getCampaigns();
//
//            // Получение баланса
//            $balance = $directService->getAccountBalance();

            // Отчет по производительности
//            $report = $directService->getPerformanceReport(
//                now()->subMonth(),
//                now(),
//                ['Impressions', 'Clicks', 'Cost']
//            );
//            dd($report);

            // Статистика по кампании
            $stats = $directService->getCampaignStatistics(
                12345,
                now()->subWeek(),
                now()
            );

            dd($stats);

        } catch (YandexDirectApiException $e) {
            // Обработка ошибок
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
