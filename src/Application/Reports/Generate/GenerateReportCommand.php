<?php

namespace Src\Application\Reports\Generate;

use DateTimeImmutable;
use Src\Domain\Reports\ReportFormat;
use Src\Domain\ValueObjects\DateTimeRange;

class GenerateReportCommand
{
    public function __construct(
        public readonly int $projectId,
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
        public readonly string $format,
        public readonly int $templateId,
        public readonly int $createdBy
    ) {}
}
