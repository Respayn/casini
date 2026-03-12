<?php

namespace Src\Domain\Reports;

use Src\Domain\Projects\Project;

class ReportNamingService
{
    public function generateName(Report $report, Project $project): string
    {
        return 'Отчет.' . $report->getFormat()->value;
    }
}
