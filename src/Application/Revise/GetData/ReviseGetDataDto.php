<?php

namespace Src\Application\Revise\GetData;

use Carbon\Carbon;

class ReviseGetDataDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly iterable $clients = [],
        public readonly ?Carbon $date = null,
        public readonly float $income = 0,
        public readonly float|string $outcome = '-',
        public readonly float $credit = 0,
        public readonly int $creditCount = 0,
        public readonly int $incomeCount = 0,
        public readonly float $cabinetReplenishment = 0,
        public readonly int $cabinetReplenishmentCount = 0,
        public readonly float $workActsSum = 0,
        public readonly array $workActs = [],
    ) {}
}
