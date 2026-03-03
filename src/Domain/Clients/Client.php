<?php

namespace Src\Domain\Clients;

class Client
{
    private function __construct(
        private ?int $id,
        private string $name,
        private int $managerId
    ) {}

    public static function restore(
        int $id,
        string $name,
        int $managerId
    ): Client {
        return new self(
            $id,
            $name,
            $managerId
        );
    }

    public function getId(): int
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
}
