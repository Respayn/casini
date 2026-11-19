<?php

namespace Src\Application\Reports\Generate;

use DateTimeImmutable;
use Src\Domain\Reports\ReportColumnGrouping;
use Src\Domain\Reports\ReportDateSort;

class GenerateReportCommand
{
    public function __construct(
        public readonly int $projectId,
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
        public readonly string $format,
        public readonly int $templateId,
        public readonly int $createdBy,
        public readonly ?int $reportId = null,
        public readonly ReportColumnGrouping $columnGrouping = ReportColumnGrouping::Months,
        public readonly ReportDateSort $dateSort = ReportDateSort::NewestFirst,
    ) {}
}
