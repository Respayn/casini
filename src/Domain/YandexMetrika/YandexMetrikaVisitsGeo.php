<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;

class YandexMetrikaVisitsGeo
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private DateTimeImmutable $month,
        private string $city,
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
            $data['city'],
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

    public function getCity(): string
    {
        return $this->city;
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
