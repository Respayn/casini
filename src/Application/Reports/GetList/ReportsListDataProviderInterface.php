<?php

namespace Src\Application\Reports\GetList;

use Src\Domain\ValueObjects\DateTimeRange;

interface ReportsListDataProviderInterface
{
    /**
     * @return ReportListItemDto[]
     */
    public function getList(bool $showInactiveProjects, DateTimeRange $period, ?int $userId): array;
}
