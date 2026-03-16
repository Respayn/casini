<?php

namespace Src\Application\Reports\GetList;

use DateTimeImmutable;
use IntlDateFormatter;

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

    public function periodLabel(): string
    {
        $formatter = new IntlDateFormatter('ru_RU', IntlDateFormatter::SHORT, IntlDateFormatter::NONE);

        if ($this->periodStart->format('Y') === $this->periodEnd->format('Y')
            && $this->periodStart->format('m') === $this->periodEnd->format('m')
        ) {
            $formatter->setPattern('LLLL yyyy');
            return mb_ucfirst($formatter->format($this->periodStart));
        }

        $formatter->setPattern('LLLL yyyy');
        return mb_ucfirst($formatter->format($this->periodStart)) . ' - ' . mb_ucfirst($formatter->format($this->periodEnd));
    }
}
