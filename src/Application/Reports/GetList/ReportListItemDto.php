<?php

namespace Src\Application\Reports\GetList;

use DateTimeImmutable;

class ReportListItemDto
{
    public function __construct(
        public DateTimeImmutable $createdAt,
        public string $templateName,
        public int $clientId,
        public string $clientName,
        public string $reportId,
        public string $channel,
        public int $projectId,
        public string $projectName,
        public DateTimeImmutable $periodStart,
        public DateTimeImmutable $periodEnd,
        public string $specialistName,
        public string $format,
        public bool $isReady,
        public bool $isAccepted,
        public bool $isSent
    ) {}
}
