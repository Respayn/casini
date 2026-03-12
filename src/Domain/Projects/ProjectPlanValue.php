<?php

namespace Src\Domain\Projects;

use DateTimeImmutable;

/**
 * Плановое значение показателя проекта.
 */
class ProjectPlanValue
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $parameterCode,
        private ?float $value,
        private DateTimeImmutable $yearMonthDate
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            $data['parameter_code'],
            $data['value'] !== null ? (float) $data['value'] : null,
            new DateTimeImmutable($data['year_month_date'])
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getParameterCode(): string
    {
        return $this->parameterCode;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function getYearMonthDate(): DateTimeImmutable
    {
        return $this->yearMonthDate;
    }
}
