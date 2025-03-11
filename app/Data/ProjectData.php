<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ProjectData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $domain,
        public int $clientId,
        public ?int $specialistId,
        public int $departmentId,
        public ?string $projectType,
        public ?string $kpi,
        public bool $isActive,
        public bool $isInternal,
        public ?string $trafficAttribution,
        public ?string $metrikaCounter,
        public ?string $metrikaTargets,
        public ?string $googleAdsClientId,
        public ?string $contractNumber,
        public ?string $additionalContractNumber,
        public ?string $recommendationUrl,
        public ?string $legalEntity,
        public ?string $inn,
    ) {}
}
