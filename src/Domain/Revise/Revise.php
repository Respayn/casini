<?php

namespace Src\Domain\Revise;

use Carbon\Carbon;

class Revise
{
    public function __construct(
        private Carbon $date,
        private float $income,
        private float|string $outcome,
        private float $credit,
        private int $creditCount,
        private int $incomeCount,
        private float $cabinetReplenishment,
        private int $cabinetReplenishmentCount,
        private float $workActsSum,
        private array $workActs
    ) {}

    public static function create(
        Carbon $date,
        float $income,
        float|string $outcome,
        float $credit,
        int $creditCount,
        int $incomeCount,
        float $cabinetReplenishment,
        int $cabinetReplenishmentCount,
        float $workActsSum,
        array $workActs
    ): Revise {
        return new self(
            $date,
            $income,
            $outcome,
            $credit,
            $creditCount,
            $incomeCount,
            $cabinetReplenishment,
            $cabinetReplenishmentCount,
            $workActsSum,
            $workActs
        );
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function getIncome(): float
    {
        return $this->income;
    }

    public function getOutcome(): float|string
    {
        return $this->outcome;
    }

    public function getCredit(): float
    {
        return $this->credit;
    }

    public function getCreditCount(): int
    {
        return $this->creditCount;
    }

    public function getIncomeCount(): int
    {
        return $this->incomeCount;
    }

    public function getCabinetReplenishment(): float
    {
        return $this->cabinetReplenishment;
    }

    public function getCabinetReplenishmentCount(): int
    {
        return $this->cabinetReplenishmentCount;
    }

    public function getWorkActsSum(): float
    {
        return $this->workActsSum;
    }

    public function getWorkActs(): array
    {
        return $this->workActs;
    }
}
