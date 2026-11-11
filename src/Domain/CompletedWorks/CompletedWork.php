<?php

namespace Src\Domain\CompletedWorks;

use DateTimeImmutable;

class CompletedWork
{
    public function __construct(
        private readonly int $id,
        private readonly int $projectId,
        private readonly string $title,
        private readonly DateTimeImmutable $completedAt,
        private readonly int $depth = 0,
        private readonly int $sortOrder = 0,
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

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
