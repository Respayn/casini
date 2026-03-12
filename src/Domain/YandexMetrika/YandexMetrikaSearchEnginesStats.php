<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;

class YandexMetrikaSearchEnginesStats
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $searchEngine,
        private DateTimeImmutable $month,
        private int $visits,
        private int $conversions
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            $data['search_engine'],
            new DateTimeImmutable($data['month']),
            (int) $data['visits'],
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

    public function getMonth(): DateTimeImmutable
    {
        return $this->month;
    }

    public function getVisits(): int
    {
        return $this->visits;
    }

    public function getConversions(): int
    {
        return $this->conversions;
    }
}
