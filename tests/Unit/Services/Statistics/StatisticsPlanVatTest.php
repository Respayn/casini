<?php

namespace Tests\Unit\Services\Statistics;

use App\Services\Statistics\StatisticsService;
use ReflectionMethod;
use ReflectionProperty;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class StatisticsPlanVatTest extends TestCase
{
    private StatisticsService $service;

    private ReflectionMethod $resolvePlanCell;

    private ReflectionProperty $includeVat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(StatisticsService::class);
        $this->resolvePlanCell = new ReflectionMethod(StatisticsService::class, 'resolvePlanCell');
        $this->includeVat = new ReflectionProperty(StatisticsService::class, 'includeVat');
    }

    public function test_context_leads_money_gets_vat_counters_stay_net(): void
    {
        $netMoney = 409.84;
        $leads = (float) round(12295.2 / $netMoney);
        $plans = [
            200 => [
                ['value' => $netMoney, 'format' => 'currency'],
                ['value' => 12295.2, 'format' => 'currency'],
                ['value' => $leads, 'format' => null],
            ],
        ];

        $withVat = $this->resolve(true, $plans, ProjectType::CONTEXT_AD, Kpi::LEADS);
        $this->assertSame(500.0, $withVat[0]['value']);
        $this->assertSame(15000.14, $withVat[1]['value']);
        $this->assertSame($leads, $withVat[2]['value']);
        $this->assertSame('cpl', $withVat[0]['code']);
        $this->assertSame('budget', $withVat[1]['code']);
        $this->assertSame('leads', $withVat[2]['code']);

        $withoutVat = $this->resolve(false, $plans, ProjectType::CONTEXT_AD, Kpi::LEADS);
        $this->assertSame(409.84, $withoutVat[0]['value']);
        $this->assertSame(12295.2, $withoutVat[1]['value']);
        $this->assertSame($leads, $withoutVat[2]['value']);
    }

    public function test_context_traffic_money_gets_vat_visits_stay_net(): void
    {
        $netMoney = 409.84;
        $visits = (float) round(12295.2 / $netMoney);
        $plans = [
            1 => [
                ['value' => $netMoney, 'format' => 'currency'],
                ['value' => 12295.2, 'format' => 'currency'],
                ['value' => $visits, 'format' => null],
            ],
        ];

        $withVat = $this->resolve(true, $plans, ProjectType::CONTEXT_AD, Kpi::TRAFFIC);
        $this->assertSame(500.0, $withVat[0]['value']);
        $this->assertSame(15000.14, $withVat[1]['value']);
        $this->assertSame($visits, $withVat[2]['value']);
        $this->assertSame('cpc', $withVat[0]['code']);
        $this->assertSame('visits', $withVat[2]['code']);

        $withoutVat = $this->resolve(false, $plans, ProjectType::CONTEXT_AD, Kpi::TRAFFIC);
        $this->assertSame(409.84, $withoutVat[0]['value']);
        $this->assertSame($visits, $withoutVat[2]['value']);
    }

    public function test_seo_traffic_plans_ignore_vat_flag(): void
    {
        $plans = [
            1 => [
                ['value' => 1000.0, 'format' => null],
                ['value' => 50.0, 'format' => null],
            ],
        ];

        $withVat = $this->resolve(true, $plans, ProjectType::SEO_PROMOTION, Kpi::TRAFFIC);
        $withoutVat = $this->resolve(false, $plans, ProjectType::SEO_PROMOTION, Kpi::TRAFFIC);

        $this->assertSame(1000.0, $withVat[0]['value']);
        $this->assertSame(50.0, $withVat[1]['value']);
        $this->assertSame($withoutVat[0]['value'], $withVat[0]['value']);
        $this->assertSame($withoutVat[1]['value'], $withVat[1]['value']);
        $this->assertSame('visits', $withVat[0]['code']);
        $this->assertSame('conversions', $withVat[1]['code']);
    }

    public function test_seo_positions_plans_ignore_vat_flag(): void
    {
        $plans = [
            1 => [
                ['value' => 35.0, 'format' => 'percent'],
                ['value' => 12.0, 'format' => null],
            ],
        ];

        $withVat = $this->resolve(true, $plans, ProjectType::SEO_PROMOTION, Kpi::POSITIONS);
        $withoutVat = $this->resolve(false, $plans, ProjectType::SEO_PROMOTION, Kpi::POSITIONS);

        $this->assertSame(35.0, $withVat[0]['value']);
        $this->assertSame(12.0, $withVat[1]['value']);
        $this->assertSame($withoutVat[0]['value'], $withVat[0]['value']);
        $this->assertSame('top_percent', $withVat[0]['code']);
        $this->assertSame('conversions', $withVat[1]['code']);
    }

    /**
     * @param  array<int, list<array{value: mixed, format: mixed}>>  $plans
     * @return list<array{value: mixed, format: mixed, highlight: bool, code: ?string}>
     */
    private function resolve(
        bool $includeVat,
        array $plans,
        ProjectType $projectType,
        Kpi $kpi,
    ): array {
        $this->includeVat->setValue($this->service, $includeVat);
        $projectId = (int) array_key_first($plans);

        return $this->resolvePlanCell->invoke(
            $this->service,
            $plans,
            $projectId,
            $projectType,
            $kpi,
        );
    }
}
