<?php

namespace Tests\Feature;

use App\Data\YandexDirect\CampaignDTO;
use App\Data\YandexDirect\MonthlyExpenseDTO;
use App\Data\YandexDirect\PerformanceReportDTO;
use App\Services\YandexDirectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class YandexDirectServiceTest extends TestCase
{
    private YandexDirectService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(YandexDirectService::class);
        $this->service->setupClient(
            config('services.yandex_direct.test_token'),
            config('services.yandex_direct.test_client_login')
        );
    }

    /** @test */
    public function test_campaigns_correctly()
    {
        // Запрос реальных данных
        $campaigns = $this->service->getCampaigns();

        // Базовые проверки
        $this->assertInstanceOf(Collection::class, $campaigns);

        if ($campaigns->isNotEmpty()) {
            // Проверка структуры первого элемента
            $firstCampaign = $campaigns->first();

            $this->assertInstanceOf(CampaignDTO::class, $firstCampaign);
            $this->assertIsInt($firstCampaign->id);
            $this->assertIsString($firstCampaign->name);
            $this->assertContains($firstCampaign->status, [
                'ACCEPTED', 'DRAFT', 'MODERATION',
                'REJECTED', 'ARCHIVED', 'ACTIVE'
            ]);

            $resultData = (array)$firstCampaign;

            $this->assertArrayHasKey('name', $resultData);
        }
    }

    /** @test */
    public function test_account_balance()
    {
        $service = app(YandexDirectService::class);
        $service->setupClient(
            config('services.yandex_direct.test_token'), // используй .env
            config('services.yandex_direct.test_client_login')
        );

        $balance = $service->getAccountBalance();
        $this->assertIsNumeric($balance);
    }

    /** @test */
    public function test_performance_report()
    {
        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now();

        $report = $this->service->getPerformanceReport(
            $startDate,
            $endDate,
            ['Impressions', 'Clicks', 'Cost']
        );

        $this->assertInstanceOf(Collection::class, $report);

        if ($report->isNotEmpty()) {
            $this->assertInstanceOf(PerformanceReportDTO::class, $report->first());
            $this->assertIsInt($report->first()->impressions);
            $this->assertIsInt($report->first()->clicks);
            $this->assertIsFloat($report->first()->cost);
        }
    }

    /** @test */
    public function test_validates_date_ranges()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->getPerformanceReport(
            Carbon::now()->addDay(),
            Carbon::now()
        );
    }

    /** @test */
    public function test_project_expenses_calculation()
    {
        $start = Carbon::now()->subMonth();
        $end = Carbon::now();

        $total = $this->service->getProjectExpenses($start, $end);
        $monthly = $this->service->getProjectExpensesByMonth($start, $end);

        $this->assertIsFloat($total);
        $this->assertInstanceOf(Collection::class, $monthly);

        if ($monthly->isNotEmpty()) {
            $this->assertInstanceOf(MonthlyExpenseDTO::class, $monthly->first());
        }
    }
}
