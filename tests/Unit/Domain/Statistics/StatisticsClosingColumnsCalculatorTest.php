<?php

namespace Tests\Unit\Domain\Statistics;

use App\Domain\Statistics\Services\StatisticsClosingColumnsCalculator;
use App\Models\Project;
use App\Models\ProjectBonusCondition;
use App\Models\ProjectBonusInterval;
use App\Services\BonusService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Src\Planning\Application\ProjectPlanService;
use Tests\TestCase;

class StatisticsClosingColumnsCalculatorTest extends TestCase
{
    private StatisticsClosingColumnsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new StatisticsClosingColumnsCalculator(
            $this->app->make(ProjectPlanService::class),
            new BonusService,
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_summary_empty_without_last_day_slice(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $month = Carbon::parse('2026-07-01');

        $result = $this->calculator->calculate(
            $project,
            $month,
            $this->leadsPlan(100),
            ['2026-07-01' => 1000.0, '2026-07-15' => 2000.0],
            ['2026-07-01' => 5, '2026-07-15' => 10],
            [],
            [],
            Carbon::parse('2026-08-10'),
        );

        // Нет среза за 31.07 → итог пустой по всем слотам
        foreach ($result['summary'] as $slot) {
            $this->assertNull($slot['value']);
        }
        $this->assertSame('dash', $result['bonuses']['kind']);
    }

    public function test_summary_sums_leads_and_computes_cpl_when_month_closed(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $month = Carbon::parse('2026-07-01');
        $lastDay = '2026-07-31';

        $spend = [
            '2026-07-01' => 1000.0,
            '2026-07-15' => 2000.0,
            $lastDay => 500.0,
        ];
        $leads = [
            '2026-07-01' => 5,
            '2026-07-15' => 10,
            $lastDay => 5,
        ];

        $result = $this->calculator->calculate(
            $project,
            $month,
            $this->leadsPlan(100),
            $spend,
            $leads,
            [],
            [],
            Carbon::parse('2026-08-01'),
        );

        // cpl (0), budget (1), leads (2)
        $this->assertSame(3500.0, $result['summary'][1]['value']);
        $this->assertSame(20, $result['summary'][2]['value']);
        $this->assertSame(175.0, $result['summary'][0]['value']); // 3500/20
        $this->assertSame(20, $result['summary'][2]['plan_percent']); // 20/100
        $this->assertNull($result['summary'][1]['plan_percent']); // план бюджета не задан
    }

    public function test_summary_plan_percent_when_plan_set(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $month = Carbon::parse('2026-07-01');
        $lastDay = '2026-07-31';

        $result = $this->calculator->calculate(
            $project,
            $month,
            [
                ['value' => 200, 'format' => 'currency'],
                ['value' => 7000, 'format' => 'currency'],
                ['value' => 40, 'format' => null],
            ],
            [
                '2026-07-01' => 1000.0,
                '2026-07-15' => 2000.0,
                $lastDay => 500.0,
            ],
            [
                '2026-07-01' => 5,
                '2026-07-15' => 10,
                $lastDay => 5,
            ],
            [],
            [],
            Carbon::parse('2026-08-01'),
        );

        // CPL 175 / 200 = 88%, budget 3500/7000 = 50%, leads 20/40 = 50%
        $this->assertSame(88, $result['summary'][0]['plan_percent']);
        $this->assertSame(50, $result['summary'][1]['plan_percent']);
        $this->assertSame(50, $result['summary'][2]['plan_percent']);
    }

    public function test_prediction_forecast_for_leads_primary_slot(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $month = Carbon::parse('2026-08-01');
        Carbon::setTestNow(Carbon::parse('2026-08-11')); // вчера = 10.08 → 10 прошедших дней

        $leads = [];
        for ($d = 1; $d <= 10; $d++) {
            $leads[sprintf('2026-08-%02d', $d)] = 2;
        }

        $result = $this->calculator->calculate(
            $project,
            $month,
            $this->leadsPlan(100),
            [],
            $leads,
            [],
            [],
            Carbon::parse('2026-08-11'),
        );

        // факт 20 / 10 * 31 = 62
        $this->assertSame('forecast', $result['prediction'][2]['kind']);
        $this->assertSame(62, $result['prediction'][2]['value']);
        $this->assertNull($result['prediction'][0]['value']);
        $this->assertNull($result['prediction'][1]['value']);
    }

    public function test_prediction_insufficient_data_when_fewer_than_three_days(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $month = Carbon::parse('2026-08-01');

        $result = $this->calculator->calculate(
            $project,
            $month,
            $this->leadsPlan(100),
            [],
            ['2026-08-01' => 3, '2026-08-02' => 4],
            [],
            [],
            Carbon::parse('2026-08-10'),
        );

        $this->assertSame('insufficient', $result['prediction'][2]['kind']);
    }

    public function test_prediction_dash_for_positions_kpi(): void
    {
        $project = $this->makeProject(ProjectType::SEO_PROMOTION, Kpi::POSITIONS);
        $month = Carbon::parse('2026-07-01');
        $lastDay = '2026-07-31';

        $tops = [];
        for ($d = 1; $d <= 31; $d++) {
            $tops[sprintf('2026-07-%02d', $d)] = 40.0;
        }
        $tops[$lastDay] = 50.0;

        $result = $this->calculator->calculate(
            $project,
            $month,
            [
                ['value' => 50, 'format' => 'percent'],
                ['value' => null, 'format' => null],
            ],
            [],
            [],
            $tops,
            [],
            Carbon::parse('2026-08-01'),
        );

        foreach ($result['prediction'] as $slot) {
            $this->assertNull($slot['value'] ?? null);
            $this->assertArrayNotHasKey('kind', $slot);
        }
        $this->assertSame(50.0, $result['summary'][0]['value']);
    }

    public function test_bonuses_not_configured_when_disabled(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $condition = new ProjectBonusCondition([
            'bonuses_enabled' => false,
            'calculate_in_percentage' => false,
        ]);
        $condition->setRelation('intervals', new Collection);
        $project->setRelation('bonusCondition', $condition);

        $result = $this->closedLeadsMonth($project);

        $this->assertSame('not_configured', $result['bonuses']['kind']);
    }

    public function test_bonuses_fill_check_when_percent_without_client_payment(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $condition = new ProjectBonusCondition([
            'bonuses_enabled' => true,
            'calculate_in_percentage' => true,
            'client_payment' => null,
        ]);
        $condition->setRelation('intervals', new Collection);
        $project->setRelation('bonusCondition', $condition);

        $result = $this->closedLeadsMonth($project);

        $this->assertSame('fill_check', $result['bonuses']['kind']);
    }

    public function test_bonuses_fixed_amount_and_guarantee(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $condition = new ProjectBonusCondition([
            'bonuses_enabled' => true,
            'calculate_in_percentage' => false,
            'client_payment' => 100000,
        ]);
        $intervals = new Collection([
            new ProjectBonusInterval([
                'from_percentage' => 0,
                'to_percentage' => 89,
                'bonus_amount' => -5000,
            ]),
            new ProjectBonusInterval([
                'from_percentage' => 90,
                'to_percentage' => 200,
                'bonus_amount' => 15000,
            ]),
        ]);
        $condition->setRelation('intervals', $intervals);
        $project->setRelation('bonusCondition', $condition);

        // Итог лидов 20 при плане 100 → 20% → гарантия -5000
        $result = $this->closedLeadsMonth($project, planLeads: 100);
        $this->assertSame('amount', $result['bonuses']['kind']);
        $this->assertSame(-5000.0, $result['bonuses']['value']);

        // Итог 20 при плане 20 → 100% → бонус 15000
        $resultOk = $this->closedLeadsMonth($project, planLeads: 20);
        $this->assertSame(15000.0, $resultOk['bonuses']['value']);
    }

    public function test_bonuses_percent_of_check(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $condition = new ProjectBonusCondition([
            'bonuses_enabled' => true,
            'calculate_in_percentage' => true,
            'client_payment' => 100000,
        ]);
        $condition->setRelation('intervals', new Collection([
            new ProjectBonusInterval([
                'from_percentage' => 100,
                'to_percentage' => 200,
                'bonus_percentage' => 10,
            ]),
        ]));
        $project->setRelation('bonusCondition', $condition);

        $result = $this->closedLeadsMonth($project, planLeads: 20);
        $this->assertSame(10000.0, $result['bonuses']['value']);
    }

    public function test_bonuses_zero_when_no_interval_matches(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $condition = new ProjectBonusCondition([
            'bonuses_enabled' => true,
            'calculate_in_percentage' => false,
        ]);
        $condition->setRelation('intervals', new Collection([
            new ProjectBonusInterval([
                'from_percentage' => 90,
                'to_percentage' => 200,
                'bonus_amount' => 1000,
            ]),
        ]));
        $project->setRelation('bonusCondition', $condition);

        // 20% выполнения — вне интервалов
        $result = $this->closedLeadsMonth($project, planLeads: 100);
        $this->assertSame('amount', $result['bonuses']['kind']);
        $this->assertSame(0.0, $result['bonuses']['value']);
    }

    public function test_bonuses_dash_when_summary_not_ready(): void
    {
        $project = $this->makeProject(ProjectType::CONTEXT_AD, Kpi::LEADS);
        $condition = new ProjectBonusCondition(['bonuses_enabled' => true]);
        $condition->setRelation('intervals', new Collection);
        $project->setRelation('bonusCondition', $condition);

        $result = $this->calculator->calculate(
            $project,
            Carbon::parse('2026-08-01'),
            $this->leadsPlan(100),
            ['2026-08-01' => 100],
            ['2026-08-01' => 5],
            [],
            [],
            Carbon::parse('2026-08-15'),
        );

        $this->assertSame('dash', $result['bonuses']['kind']);
    }

    private function makeProject(ProjectType $type, Kpi $kpi): Project
    {
        $project = new Project([
            'project_type' => $type,
            'kpi' => $kpi,
            'name' => 'Test',
        ]);
        $project->id = 1;
        $project->setRelation('bonusCondition', null);

        return $project;
    }

    /**
     * @return list<array{value: mixed, format: mixed}>
     */
    private function leadsPlan(int $leads): array
    {
        return [
            ['value' => null, 'format' => 'currency'],
            ['value' => null, 'format' => 'currency'],
            ['value' => $leads, 'format' => null],
        ];
    }

    /**
     * @return array{summary: array, prediction: array, bonuses: array}
     */
    private function closedLeadsMonth(Project $project, int $planLeads = 100): array
    {
        $lastDay = '2026-07-31';

        return $this->calculator->calculate(
            $project,
            Carbon::parse('2026-07-01'),
            $this->leadsPlan($planLeads),
            [
                '2026-07-01' => 1000.0,
                $lastDay => 500.0,
            ],
            [
                '2026-07-01' => 15,
                $lastDay => 5,
            ],
            [],
            [],
            Carbon::parse('2026-08-01'),
        );
    }
}
