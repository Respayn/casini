<?php

namespace Src\Domain\Templates;

use DateTimeImmutable;

class Template
{
    private function __construct(
        private ?int $id,
        private string $name,
        private string $path,
        private DateTimeImmutable $createdAt
    ) {}

    public static function create(string $name, string $path): Template
    {
        return new self(
            null,
            $name,
            $path,
            new DateTimeImmutable()
        );
    }

    public static function restore(int $id, string $name, string $path, DateTimeImmutable $createdAt): Template
    {
        return new self(
            $id,
            $name,
            $path,
            $createdAt
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
