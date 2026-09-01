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
    public function test_enrich_with_spendings_totals_aggregates_group_and_report_summaries(): void
    {
        $service = new ChannelReportService(
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

        $rowOne = new TableReportRowData;
        $rowOne->id = 1;
        $rowOne->data = new Collection([
            'programming' => ['hours' => 2, 'sum' => 1000],
            'copyrighting' => ['hours' => 3, 'sum' => 500],
            'seo-links' => ['sum' => 200],
            'summary-spendings' => ['sum' => 1700],
        ]);

        $rowTwo = new TableReportRowData;
        $rowTwo->id = 2;
        $rowTwo->data = new Collection([
            'programming' => ['hours' => 1, 'sum' => 250],
            'copyrighting' => null,
            'seo-links' => ['sum' => null],
            'summary-spendings' => ['sum' => 250],
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
        $this->assertSame(['sum' => 1950.0], $group->summary->get('summary-spendings'));

        $this->assertSame([
            'hours' => 3.0,
            'sum' => 1250.0,
        ], $report->summary->get('programming'));
        $this->assertSame(['sum' => 1950.0], $report->summary->get('summary-spendings'));
    }

    public function test_enrich_with_spendings_totals_keeps_null_when_no_row_values(): void
    {
        $service = new ChannelReportService(
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

        $row = new TableReportRowData;
        $row->id = 1;
        $row->data = new Collection([
            'programming' => null,
            'copyrighting' => null,
            'seo-links' => ['sum' => null],
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
        $this->assertNull($group->summary->get('summary-spendings'));
    }
}
