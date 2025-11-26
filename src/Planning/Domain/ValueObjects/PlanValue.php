<?php

namespace Src\Planning\Domain\ValueObjects;

use Src\Shared\ValueObjects\KpiParameter;

class PlanValue
{
    private string $parameterCode;
    private int $year;
    private int $month;
    private ?float $value;

    public function __construct(string $parameterCode, int $year, int $month, ?float $value)
    {
        $this->parameterCode = $parameterCode;
        $this->year = $year;
        $this->month = $month;
        $this->value = $value;
    }

    public function getParameterCode(): string
    {
        return $this->parameterCode;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): void
    {
        $this->value = $value;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}