<?php

namespace Src\Application\Clients\Create;

class CreateClientCommand
{
    public function __construct(
        public string $name,
        public string $inn,
        public int $managerId,
        public float $initialBalance
    ) {}
}
