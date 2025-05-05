<?php

namespace App\Data\IntegrationSettings;

use App\Data\Integrations\IntegrationData;
use App\Enums\Callibri\AppealCalculationMethod;
use App\Enums\Callibri\AppealType;
use Carbon\CarbonImmutable;
use Livewire\Wireable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CallibriIntegrationSettingsData extends IntegrationSettingsData implements Wireable
{
    use WireableData;

    public function __construct(
        // Основные настройки
        #[Required, StringType]
        public string $apiToken,

        #[Required, IntegerType]
        public int $callibriProjectId,

        // Настройки фильтрации
        #[BooleanType]
        public bool $useUtmFilters = false,

        /** @var string[] */
        #[Nullable]
        public ?array $utmSources = null,

        /** @var string[] */
        #[Nullable]
        public ?array $utmCampaigns = null,

        /** @var string[] */
        #[Nullable]
        public ?array $utmMediums = null,

        // Типы и классы обращений
        /** @var AppealType[] */
        #[In([AppealType::class])]
        public array $appealTypes = [],

        #[Required]
        public AppealCalculationMethod $calculationMethod = AppealCalculationMethod::ALL,

        /** @var string[] */
        public array $appealClasses = [],

        #[WithCast(
            DateTimeInterfaceCast::class,
            format: 'Y-m-d',
            type: CarbonImmutable::class
        )]
        #[Nullable]
        public ?CarbonImmutable $reportDate = null,

        #[Nullable]
        public ?int $appealCount = null,

        // Унаследованные поля
        public ?int $integrationId = null,
        public ?IntegrationData $integration = null,
        public bool $isEnabled = true,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $updatedAt = null,
    ) {
    }

    public static function prepareForPipeline(array $properties): array
    {
        // Преобразование строк в Enum
        if (isset($properties['appeal_types'])) {
            $properties['appeal_types'] = array_map(
                fn($type) => is_string($type) ? AppealType::from($type) : $type,
                $properties['appeal_types']
            );
        }

        if (isset($properties['calculation_method']) && is_string($properties['calculation_method'])) {
            $properties['calculation_method'] = AppealCalculationMethod::from($properties['calculation_method']);
        }

        return parent::prepareForPipeline($properties);
    }
}
