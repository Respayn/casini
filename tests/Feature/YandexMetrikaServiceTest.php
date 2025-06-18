<?php

namespace Tests\Feature;

use App\Data\YandexMetrika\GoalDTO;
use App\Data\YandexMetrika\VisitReportDTO;
use App\Services\YandexMetrikaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaServiceTest extends TestCase
{
    private YandexMetrikaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(YandexMetrikaService::class);
        $this->service->setupClient(
            config('services.yandex_metrika.test_token'),
            config('services.yandex_metrika.test_client_login'),
            config('services.yandex_metrika.test_counter_id'),
        );
    }

    #[Test]
    public function test_goals_retrieval()
    {
        $goals = $this->service->getGoals();

        $this->assertInstanceOf(Collection::class, $goals);

        if ($goals->isNotEmpty()) {
            $firstGoal = $goals->first();

            $this->assertInstanceOf(GoalDTO::class, $firstGoal);
            $this->assertIsInt($firstGoal->id);
            $this->assertIsString($firstGoal->name);
            $this->assertContains($firstGoal->type, ['url', 'number', 'action']);
        }
    }

    #[Test]
    public function test_visits_report_structure()
    {
        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now();

        $report = $this->service->getVisitsReport($startDate, $endDate);

        $this->assertInstanceOf(VisitReportDTO::class, $report);

        // Проверка основных полей
        $this->assertInstanceOf(Carbon::class, $report->startDate);
        $this->assertInstanceOf(Carbon::class, $report->endDate);
        $this->assertIsInt($report->visits);
        $this->assertIsInt($report->users);

        // Проверка структуры доп. данных
        $this->assertIsArray($report->queryParams);
        $this->assertArrayHasKey('metrics', $report->queryParams);
        $this->assertIsArray($report->totals);
        $this->assertCount(2, $report->totals);
    }
}
