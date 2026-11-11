<?php

namespace Tests\Unit\Data;

use App\Data\TableReportData;
use App\Data\TableReportGroupData;
use App\Data\TableReportRowData;
use Tests\TestCase;

class TableReportDataTest extends TestCase
{
    public function test_group_without_rows_is_not_a_filled_report(): void
    {
        $report = new TableReportData;
        $group = new TableReportGroupData;
        $report->groups->push($group);

        $this->assertFalse($report->hasProjectRows());

        $group->rows->push(new TableReportRowData);
        $this->assertTrue($report->hasProjectRows());
    }
}
