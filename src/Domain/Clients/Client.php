<?php

namespace Src\Domain\Clients;

class Client
{
    private function __construct(
        private ?int $id,
        private string $name,
        private int $managerId,
        private string $inn,
        private float $initialBalance
    ) {}

    public static function create(
        string $name,
        int $managerId,
        string $inn,
        float $initialBalance = 0.0
    ) {
        return new self(
            id: null,
            name: $name,
            managerId: $managerId,
            inn: $inn,
            initialBalance: $initialBalance
        );
    }

    public static function restore(
        int $id,
        string $name,
        int $managerId,
        string $inn,
        float $initialBalance
    ): Client {
        return new self(
            id: $id,
            name: $name,
            managerId: $managerId,
            inn: $inn,
            initialBalance: $initialBalance
        );
    }

    public function update(
        string $name,
        int $managerId,
        string $inn,
        float $initialBalance
    ) {
        $this->name = $name;
        $this->managerId = $managerId;
        $this->inn = $inn;
        $this->initialBalance = $initialBalance;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getManagerId(): int
    {
        return $this->managerId;
    }

    public function getInn(): string
    {
        return $this->inn;
    }

    public function getInitialBalance(): float
    {
        return $this->initialBalance;
    }
}
