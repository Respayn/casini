<?php

namespace App\Data;

use App\Models\Project;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;

class ProjectData extends Data
{
    // TODO: Связь assistants
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $domain,
        public int $client_id,
        public ?int $specialist_id,
        public ?ProjectType $project_type,
        public ?Kpi $kpi,
        public bool $is_active,
        public bool $is_internal,
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
        public ?BonusConditionData $bonusCondition = null,
        #[DataCollectionOf(PromotionRegionData::class)]
        public ?DataCollection $promotionRegions = null,
        #[DataCollectionOf(PromotionTopicData::class)]
        public ?DataCollection $promotionTopics = null,
        #[DataCollectionOf(ProjectUtmMappingData::class)]
        public ?DataCollection $utmMappings = null,
    ) {
    }
}
