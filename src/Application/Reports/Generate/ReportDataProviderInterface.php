<?php

namespace Src\Application\Reports\Generate;

use Src\Domain\ValueObjects\DateTimeRange;

interface ReportDataProviderInterface
{
    public function getData(int $projectId, DateTimeRange $period): ReportData;
}
