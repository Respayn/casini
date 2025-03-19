<?php

namespace App\Data;

use App\Enums\ProjectType;
use App\Models\Project;
use Spatie\LaravelData\Data;

class ProjectData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $domain,
        public int $client_id,
        public ?int $specialist_id,
        public ?ProjectType $project_type,
        public ?string $kpi,
        public bool $isActive,
        public bool $isInternal,
        public ?string $traffic_attribution,
        public ?string $metrika_counter,
        public ?string $metrika_targets,
        public ?string $google_ads_client_id,
        public ?string $contract_number,
        public ?string $additional_contract_number,
        public ?string $recommendation_url,
        public ?string $legal_entity,
        public ?string $inn,
        public ?ClientData $client = null,
        public ?UserData $specialist = null,
    ) {}
}
