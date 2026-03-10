<?php

namespace Src\Domain\ValueObjects;

use DateTimeImmutable;

class DateTimeRange
{
    public function __construct(
        public readonly ?DateTimeImmutable $start,
        public readonly ?DateTimeImmutable $end
    ) {}

    public function getStart(): ?DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }
}
