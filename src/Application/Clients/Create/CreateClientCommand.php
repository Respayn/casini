<?php

namespace Src\Application\Clients\Create;

use DateTimeImmutable;

class CreateClientCommand
{
    public function __construct(
        public string $name,
        public string $inn,
        public int $managerId,
        public float $initialBalance,
        public bool $chargesAdFee = true,
        public ?DateTimeImmutable $adFeeChangedAt = null
    ) {}
}
