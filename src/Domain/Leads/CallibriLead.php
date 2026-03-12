<?php

namespace Src\Domain\Leads;

use DateTimeImmutable;

class CallibriLead
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $externalId,
        private DateTimeImmutable $date,
        private ?string $utmSource,
        private ?string $utmCampaign,
        private ?string $utmMedium,
        private ?string $utmContent,
        private ?string $utmTerm
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'],
            $data['project_id'],
            $data['external_id'],
            new DateTimeImmutable($data['date']),
            $data['utm_source'],
            $data['utm_campaign'],
            $data['utm_medium'],
            $data['utm_content'],
            $data['utm_term']
        );
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
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
