<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;

class YandexMetrikaGoalUtm
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $goalName,
        private DateTimeImmutable $achievedDate,
        private ?string $utmSource,
        private ?string $utmMedium,
        private ?string $utmCampaign,
        private ?string $utmContent,
        private ?string $utmTerm
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            $data['goal_name'],
            new DateTimeImmutable($data['achieved_date']),
            $data['utm_source'] ?? null,
            $data['utm_medium'] ?? null,
            $data['utm_campaign'] ?? null,
            $data['utm_content'] ?? null,
            $data['utm_term'] ?? null
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

    public function getGoalName(): string
    {
        return $this->goalName;
    }

    public function getAchievedDate(): DateTimeImmutable
    {
        return $this->achievedDate;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function getUtmContent(): ?string
    {
        return $this->utmContent;
    }

    public function getUtmTerm(): ?string
    {
        return $this->utmTerm;
    }
}
