<?php

namespace Src\Domain\CompletedWorks;

use DateTimeImmutable;

class CompletedWork
{
    public function __construct(
        private readonly int $id,
        private readonly int $projectId,
        private readonly string $title,
        private readonly DateTimeImmutable $completedAt
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCompletedAt(): DateTimeImmutable
    {
        return $this->completedAt;
    }
}
