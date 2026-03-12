<?php

namespace Src\Domain\YandexDirect;

use DateTimeImmutable;

class YandexDirectCampaignStats
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $campaignName,
        private ?int $campaignId,
        private int $impressions,
        private int $clicks,
        private float $costWithVat,
        private float $costWithoutVat,
        private int $conversions,
        private ?string $goalName,
        private DateTimeImmutable $date
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            $data['campaign_name'],
            isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            (int) $data['impressions'],
            (int) $data['clicks'],
            (float) $data['cost_with_vat'],
            (float) $data['cost_without_vat'],
            (int) $data['conversions'],
            $data['goal_name'] ?? null,
            new DateTimeImmutable($data['date'])
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

    public function getCampaignName(): string
    {
        return $this->campaignName;
    }

    public function getCampaignId(): ?int
    {
        return $this->campaignId;
    }

    public function getImpressions(): int
    {
        return $this->impressions;
    }

    public function getClicks(): int
    {
        return $this->clicks;
    }

    public function getCostWithVat(): float
    {
        return $this->costWithVat;
    }

    public function getCostWithoutVat(): float
    {
        return $this->costWithoutVat;
    }

    public function getConversions(): int
    {
        return $this->conversions;
    }

    public function getGoalName(): ?string
    {
        return $this->goalName;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * CTR = (клики / показы) * 100
     */
    public function getCtr(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    /**
     * CPC = расход / клики
     */
    public function getCpc(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }

        return round($this->costWithVat / $this->clicks, 2);
    }

    /**
     * CPL = расход / конверсии
     */
    public function getCpl(): float
    {
        if ($this->conversions === 0) {
            return 0.0;
        }

        return round($this->costWithVat / $this->conversions, 2);
    }
}
