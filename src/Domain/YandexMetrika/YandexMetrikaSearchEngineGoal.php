<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;

class YandexMetrikaSearchEngineGoal
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $searchEngine,
        private int $goalId,
        private string $goalName,
        private DateTimeImmutable $month,
        private int $conversions
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            $data['search_engine'],
            (int) $data['goal_id'],
            (string) $data['goal_name'],
            new DateTimeImmutable($data['month']),
            (int) $data['conversions']
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

    public function getSearchEngine(): string
    {
        return $this->searchEngine;
    }

    public function getGoalId(): int
    {
        return $this->goalId;
    }

    public function getGoalName(): string
    {
        return $this->goalName;
    }

    public function getMonth(): DateTimeImmutable
    {
        return $this->month;
    }

    public function getConversions(): int
    {
        return $this->conversions;
    }
}
