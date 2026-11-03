<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;

/**
 * Достижение цели «Директ, сводка» в разрезе кампании.
 */
class YandexMetrikaGoalDirectCampaign
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $goalName,
        private int $campaignId,
        private string $campaignName,
        private DateTimeImmutable $month,
        private int $conversions,
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            $data['goal_name'],
            (int) $data['campaign_id'],
            (string) ($data['campaign_name'] ?? ''),
            MetrikaMonth::fromMixed($data['month']),
            (int) $data['conversions'],
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

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getCampaignName(): string
    {
        return $this->campaignName;
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
