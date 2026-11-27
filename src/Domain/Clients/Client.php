<?php

namespace Src\Domain\Clients;

use DateTimeImmutable;
use InvalidArgumentException;

class Client
{
    private function __construct(
        private ?int $id,
        private string $name,
        private int $managerId,
        private string $inn,
        private float $initialBalance,
        private bool $chargesAdFee = true,
        private ?DateTimeImmutable $adFeeChangedAt = null
    ) {
        $this->assertAdFeeSettings();
    }

    public static function create(
        string $name,
        int $managerId,
        string $inn,
        float $initialBalance = 0.0,
        bool $chargesAdFee = true,
        ?DateTimeImmutable $adFeeChangedAt = null
    ) {
        return new self(
            id: null,
            name: $name,
            managerId: $managerId,
            inn: $inn,
            initialBalance: $initialBalance,
            chargesAdFee: $chargesAdFee,
            adFeeChangedAt: $adFeeChangedAt
        );
    }

    public static function restore(
        int $id,
        string $name,
        int $managerId,
        string $inn,
        float $initialBalance,
        bool $chargesAdFee = true,
        ?DateTimeImmutable $adFeeChangedAt = null
    ): Client {
        return new self(
            id: $id,
            name: $name,
            managerId: $managerId,
            inn: $inn,
            initialBalance: $initialBalance,
            chargesAdFee: $chargesAdFee,
            adFeeChangedAt: $adFeeChangedAt
        );
    }

    public function update(
        string $name,
        int $managerId,
        string $inn,
        float $initialBalance,
        bool $chargesAdFee = true,
        ?DateTimeImmutable $adFeeChangedAt = null
    ) {
        $this->name = $name;
        $this->managerId = $managerId;
        $this->inn = $inn;
        $this->initialBalance = $initialBalance;
        $this->chargesAdFee = $chargesAdFee;
        $this->adFeeChangedAt = $adFeeChangedAt;
        $this->assertAdFeeSettings();
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

    public function chargesAdFee(): bool
    {
        return $this->chargesAdFee;
    }

    /**
     * С этой даты сбор 3% в ДРС не взимается; до нее операции считаются со сбором.
     */
    public function getAdFeeChangedAt(): ?DateTimeImmutable
    {
        return $this->adFeeChangedAt;
    }

    private function assertAdFeeSettings(): void
    {
        if ($this->chargesAdFee) {
            $this->adFeeChangedAt = null;
        } elseif ($this->adFeeChangedAt === null) {
            throw new InvalidArgumentException('Для клиента без сбора нужна дата изменения расчета сбора');
        }
    }
}
