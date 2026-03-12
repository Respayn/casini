<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;

class YandexMetrikaVisitsSearchQueries
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private DateTimeImmutable $month,
        private string $phrase,
        private int $visits,
        private int $visitors,
        private int $goalReaches
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            new DateTimeImmutable($data['month']),
            $data['phrase'],
            (int) $data['visits'],
            (int) $data['visitors'],
            (int) $data['goal_reaches']
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getMonth(): DateTimeImmutable
    {
        return $this->month;
    }

    public function getPhrase(): string
    {
        return $this->phrase;
    }

    public function getVisits(): int
    {
        return $this->visits;
    }

    public function getVisitors(): int
    {
        return $this->visitors;
    }

    public function getGoalReaches(): int
    {
        return $this->goalReaches;
    }
}
