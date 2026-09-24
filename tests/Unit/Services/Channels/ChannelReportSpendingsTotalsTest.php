<?php

namespace Tests\Unit\Services\Channels;

use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use App\Repositories\ClientRepository;
use App\Repositories\IntegrationRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\RateRepository;
use App\Repositories\UserRepository;
use App\Services\BonusService;
use App\Services\Channels\ChannelDirectMetricsService;
use App\Services\Channels\ChannelReportService;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Src\Planning\Application\ProjectPlanService;
use Tests\TestCase;

class ChannelReportSpendingsTotalsTest extends TestCase
{
    private function makeService(): ChannelReportService
    {
        return new ChannelReportService(
            $this->createMock(ClientRepository::class),
            $this->createMock(ProjectRepository::class),
            $this->createMock(UserRepository::class),
            $this->createMock(IntegrationRepository::class),
            $this->createMock(RateRepository::class),
            $this->createMock(ProjectPlanService::class),
            $this->createMock(ChannelDirectMetricsService::class),
            $this->createMock(BonusService::class),
            $this->createMock(GoogleSheetsService::class),
        );
    }

    public function test_create_spendings_data_adds_labor_sums_to_summary_spendings(): void
    {
        $service = $this->makeService();

        $result = $service->createSpendingsData(
            ['hours' => 1, 'sum' => 1000],
            null,
            null,
            [],
            ['hours' => 2, 'sum' => 200],
            ['hours' => 1, 'sum' => 150],
            ['hours' => 0.5, 'sum' => 50],
            ['hours' => 3, 'sum' => 300],
        );

        $this->assertSame(['hours' => 2, 'sum' => 200], $result['seo-assistant']);
        $this->assertSame(['hours' => 1, 'sum' => 150], $result['seo-specialist']);
        $this->assertSame(['hours' => 0.5, 'sum' => 50], $result['analyst']);
        $this->assertSame(['hours' => 3, 'sum' => 300], $result['ork-manager']);
        $this->assertSame(['sum' => 1700.0], $result['summary-spendings']);
    }

    public function test_create_spendings_data_keeps_null_summary_when_all_sources_empty(): void
    {
        $service = $this->makeService();

        $result = $service->createSpendingsData(null, null, null, []);

        $this->assertNull($result['seo-assistant']);
        $this->assertNull($result['seo-specialist']);
        $this->assertNull($result['analyst']);
        $this->assertNull($result['ork-manager']);
        $this->assertSame(['sum' => null], $result['summary-spendings']);
    }

    public function test_enrich_with_spendings_totals_aggregates_group_and_report_summaries(): void
    {
        $service = $this->makeService();

        $rowOne = new TableReportRowData;
        $rowOne->id = 1;
        $rowOne->data = new Collection([
            'programming' => ['hours' => 2, 'sum' => 1000],
            'copyrighting' => ['hours' => 3, 'sum' => 500],
            'seo-links' => ['sum' => 200],
            'seo-assistant' => ['hours' => 1, 'sum' => 100],
            'seo-specialist' => ['hours' => 2, 'sum' => 200],
            'analyst' => null,
            'ork-manager' => ['hours' => 0.5, 'sum' => 50],
            'summary-spendings' => ['sum' => 2050],
        ]);

        $rowTwo = new TableReportRowData;
        $rowTwo->id = 2;
        $rowTwo->data = new Collection([
            'programming' => ['hours' => 1, 'sum' => 250],
            'copyrighting' => null,
            'seo-links' => ['sum' => null],
            'seo-assistant' => ['hours' => 1, 'sum' => 80],
            'seo-specialist' => null,
            'analyst' => ['hours' => 2, 'sum' => 120],
            'ork-manager' => null,
            'summary-spendings' => ['sum' => 450],
        ]);

        $group = new TableReportGroupData;
        $group->rows = new Collection([$rowOne, $rowTwo]);
        $group->summary = new Collection;

        $report = new TableReportData;
        $report->groups = new Collection([$group]);
        $report->summary = new Collection;

        $method = new ReflectionMethod(ChannelReportService::class, 'enrichWithSpendingsTotals');
        $method->invoke($service, $report);

        $this->assertSame([
            'hours' => 3.0,
            'sum' => 1250.0,
        ], $group->summary->get('programming'));
        $this->assertSame([
            'hours' => 3.0,
            'sum' => 500.0,
        ], $group->summary->get('copyrighting'));
        $this->assertSame(['sum' => 200.0], $group->summary->get('seo-links'));
        $this->assertSame([
            'hours' => 2.0,
            'sum' => 180.0,
        ], $group->summary->get('seo-assistant'));
        $this->assertSame([
            'hours' => 2.0,
            'sum' => 200.0,
        ], $group->summary->get('seo-specialist'));
        $this->assertSame([
            'hours' => 2.0,
            'sum' => 120.0,
        ], $group->summary->get('analyst'));
        $this->assertSame([
            'hours' => 0.5,
            'sum' => 50.0,
        ], $group->summary->get('ork-manager'));
        $this->assertSame(['sum' => 2500.0], $group->summary->get('summary-spendings'));

        $this->assertSame([
            'hours' => 3.0,
            'sum' => 1250.0,
        ], $report->summary->get('programming'));
        $this->assertSame([
            'hours' => 2.0,
            'sum' => 180.0,
        ], $report->summary->get('seo-assistant'));
        $this->assertSame(['sum' => 2500.0], $report->summary->get('summary-spendings'));
    }

    public function test_enrich_with_spendings_totals_keeps_null_when_no_row_values(): void
    {
        $service = $this->makeService();

        $row = new TableReportRowData;
        $row->id = 1;
        $row->data = new Collection([
            'programming' => null,
            'copyrighting' => null,
            'seo-links' => ['sum' => null],
            'seo-assistant' => null,
            'seo-specialist' => null,
            'analyst' => null,
            'ork-manager' => null,
            'summary-spendings' => ['sum' => null],
        ]);

        $group = new TableReportGroupData;
        $group->rows = new Collection([$row]);
        $group->summary = new Collection;

        $report = new TableReportData;
        $report->groups = new Collection([$group]);
        $report->summary = new Collection;

        $method = new ReflectionMethod(ChannelReportService::class, 'enrichWithSpendingsTotals');
        $method->invoke($service, $report);

        $this->assertNull($group->summary->get('programming'));
        $this->assertNull($group->summary->get('copyrighting'));
        $this->assertNull($group->summary->get('seo-links'));
        $this->assertNull($group->summary->get('seo-assistant'));
        $this->assertNull($group->summary->get('seo-specialist'));
        $this->assertNull($group->summary->get('analyst'));
        $this->assertNull($group->summary->get('ork-manager'));
        $this->assertNull($group->summary->get('summary-spendings'));
    }
}
