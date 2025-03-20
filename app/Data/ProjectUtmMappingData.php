<?php

namespace App\Data;

use App\Enums\UtmType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ProjectUtmMappingData extends Data
{
    public function __construct(
        public ?int $id,
        public int $project_id,
        public UtmType $utm_type,
        public string $utm_value,
        public string $replacement_value,
        public ?CarbonImmutable $created_at = null,
        public ?CarbonImmutable $updated_at = null,
        public ?ProjectData $project = null,
    ) {
    }

    public function toUpsertArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'utm_type' => $this->utm_type,
            'utm_value' => $this->utm_value,
            'replacement_value' => $this->replacement_value,
        ];
    }
}
