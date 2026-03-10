<?php

namespace Src\Application\Revise\GetData;

use Carbon\Carbon;

class ReviseGetDataDto
{
    public function __construct(
        public readonly Carbon $date,
        public readonly float $income,
        public readonly float|string $outcome,
        public readonly float $credit,
        public readonly int $creditCount,
        public readonly int $incomeCount,
        public readonly float $cabinetReplenishment,
        public readonly int $cabinetReplenishmentCount,
        public readonly float $workActsSum,
        public readonly array $workActs,
    ) {}
}
