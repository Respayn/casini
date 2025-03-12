<?php

namespace App\Data;

use Illuminate\Support\Str;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ProjectData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $domain,
        public int $client_id,
        public ?int $specialist_id,
        public int $department_id,
        public ?string $project_type,
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
    ) {}
}
