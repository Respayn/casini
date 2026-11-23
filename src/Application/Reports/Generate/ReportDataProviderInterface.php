<?php

namespace Src\Application\Reports\Generate;

use Src\Domain\Reports\ReportColumnGrouping;
use Src\Domain\Reports\ReportDateSort;
use Src\Domain\ValueObjects\DateTimeRange;

interface ReportDataProviderInterface
{
    public function getData(
        int $projectId,
        DateTimeRange $period,
        ReportColumnGrouping $columnGrouping = ReportColumnGrouping::Months,
        ReportDateSort $dateSort = ReportDateSort::NewestFirst,
    ): ReportData;
}
