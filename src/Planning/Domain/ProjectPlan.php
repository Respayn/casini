<?php

namespace Src\Planning\Domain;

use Src\Planning\Domain\ValueObjects\QuarterApproval;
use Src\Shared\ValueObjects\Quarter;

class ProjectPlan
{
    private Project $project;
    private int $year;

    /** @var QuarterApproval[] */
    private array $quarterApprovals = [];

    public function __construct(Project $project, int $year)
    {
        $this->project = $project;
        $this->year = $year;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setMonthlyValue(string $parameterCode, int $month, ?float $value): void
    {
        $this->project->setPlanValue($parameterCode, $this->year, $month, $value);
    }

    public function getMonthlyValue(string $parameterCode, int $month): ?float
    {
        return $this->project->getPlanValue($parameterCode, $this->year, $month);
    }

    public function getAllMonthlyValues(): array
    {
        $values = [];
        foreach ($this->project->getPlanValues() as $planValue) {
            $values[$planValue->getParameterCode()][$planValue->getMonth()] = $planValue->getValue();
        }

        return $values;
    }

    public function setQuarterApproval(Quarter $quarter, bool $approved): void
    {
        $quarterNum = $quarter->getNumber();
        $this->quarterApprovals[$quarterNum] = new QuarterApproval($quarter, $approved);
    }

    public function isQuarterApproved(Quarter $quarter): bool
    {
        $quarterNum = $quarter->getNumber();
        return isset($this->quarterApprovals[$quarterNum])
            && $this->quarterApprovals[$quarterNum]->isApproved();
    }

    public function getQuarterApprovals(): array
    {
        $approvals = [];
        foreach ($this->quarterApprovals as $quarterNum => $approval) {
            $approvals[$quarterNum] = $approval->isApproved();
        }

        return $approvals;
    }
}
