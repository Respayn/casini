<?php

namespace Src\Application\Clients\Update;

use DateTimeImmutable;

class UpdateClientCommand
{
    public function __construct(
        public int $id,
        public string $name,
        public string $inn,
        public int $managerId,
        public float $initialBalance,
        public bool $chargesAdFee = true,
        public ?DateTimeImmutable $adFeeChangedAt = null
    ) {}
}
