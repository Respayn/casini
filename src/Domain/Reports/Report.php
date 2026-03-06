<?php

namespace Src\Domain\Reports;

use DateTimeImmutable;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\ValueObjects\ProjectType;

class Report
{
    private function __construct(
        private ?int $id,
        private DateTimeImmutable $createdAt,
        private int $templateId,
        private int $clientId,
        private ProjectType $projectType,
        private int $projectId,
        private DateTimeRange $period,
        private int $specialistId,
        private int $managerId,
        private ReportFormat $format,
        private bool $isReady,
        private bool $isAccepted,
        private bool $isSent,
        private int $createdBy,
        private ?string $path
    ) {}

    public static function create(
        int $templateId,
        int $clientId,
        ProjectType $projectType,
        int $projectId,
        DateTimeRange $period,
        int $specialistId,
        int $managerId,
        ReportFormat $format,
        bool $isReady,
        bool $isAccepted,
        bool $isSent,
        int $createdBy,
        ?string $path = null
    ): Report {
        return new self(
            null,
            new DateTimeImmutable(),
            $templateId,
            $clientId,
            $projectType,
            $projectId,
            $period,
            $specialistId,
            $managerId,
            $format,
            $isReady,
            $isAccepted,
            $isSent,
            $createdBy,
            $path
        );
    }

    public static function restore(
        int $id,
        DateTimeImmutable $createdAt,
        int $templateId,
        int $clientId,
        ProjectType $projectType,
        int $projectId,
        DateTimeRange $period,
        int $specialistId,
        int $managerId,
        ReportFormat $format,
        bool $isReady,
        bool $isAccepted,
        bool $isSent,
        int $createdBy,
        string $path
    ) {
        return new self(
            $id,
            $createdAt,
            $templateId,
            $clientId,
            $projectType,
            $projectId,
            $period,
            $specialistId,
            $managerId,
            $format,
            $isReady,
            $isAccepted,
            $isSent,
            $createdBy,
            $path
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTemplateId(): int
    {
        return $this->templateId;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getProjectType(): ProjectType
    {
        return $this->projectType;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getPeriod(): DateTimeRange
    {
        return $this->period;
    }

    public function getPeriodStart(): DateTimeImmutable
    {
        return $this->period->getStart();
    }

    public function getPeriodEnd(): DateTimeImmutable
    {
        return $this->period->getEnd();
    }

    public function getSpecialistId(): int
    {
        return $this->specialistId;
    }

    public function getManagerId(): int
    {
        return $this->managerId;
    }

    public function getFormat(): ReportFormat
    {
        return $this->format;
    }

    public function getIsReady(): bool
    {
        return $this->isReady;
    }

    public function setIsReady(bool $isReady): void
    {
        $this->isReady = $isReady;
    }

    public function getIsAccepted(): bool
    {
        return $this->isAccepted;
    }

    public function setIsAccepted(bool $isAccepted): void
    {
        $this->isAccepted = $isAccepted;
    }

    public function getIsSent(): bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): void
    {
        $this->isSent = $isSent;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getName(): string
    {
        return 'Отчет';
    }
}
