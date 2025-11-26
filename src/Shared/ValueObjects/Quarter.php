<?php

namespace Src\Shared\ValueObjects;

final class Quarter
{
    private int $number;

    public function __construct(int $number)
    {
        if ($number < 1 || $number > 4) {
            throw new \InvalidArgumentException('Quarter number must be between 1 and 4.');
        }

        $this->number = $number;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getMonths(): array
    {
        return match($this->number) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        };
    }

    public function equals(Quarter $other): bool
    {
        return $this->number === $other->getNumber();
    }
}