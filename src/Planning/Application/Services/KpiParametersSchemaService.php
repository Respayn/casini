<?php

namespace Src\Planning\Application\Services;

use Src\Planning\Application\Factories\ContextAdKpiParametersSchemaFactory;
use Src\Planning\Application\Factories\SeoPromotionKpiParametersSchemaFactory;
use Src\Planning\Domain\Factories\KpiParametersSchemaFactoryInterface;
use Src\Planning\Domain\ValueObjects\KpiParametersSchema;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;

class KpiParametersSchemaService
{
    /** @var KpiParametersSchemaFactoryInterface[] */
    private array $factories;

    public function __construct()
    {
        $this->factories = [
            new ContextAdKpiParametersSchemaFactory(),
            new SeoPromotionKpiParametersSchemaFactory()
        ];
    }

    public function createSchema(ProjectType $type, Kpi $kpi): KpiParametersSchema
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($type, $kpi)) {
                return $factory->create($type, $kpi);
            }
        }

        throw new \InvalidArgumentException(
            sprintf('No factory found for %s with %s', $type->value, $kpi->value)
        );
    }

    public function supports(ProjectType $type, Kpi $kpi): bool
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($type, $kpi)) {
                return true;
            }
        }
        return false;
    }

    public function getAvailableCombinations(): array
    {
        $combinations = [];
        foreach (ProjectType::cases() as $type) {
            foreach (Kpi::cases() as $kpi) {
                if ($this->supports($type, $kpi)) {
                    $combinations[] = [
                        'type' => $type,
                        'kpi' => $kpi,
                        'label' => sprintf('%s - %s', $type->label(), $kpi->label())
                    ];
                }
            }
        }
        return $combinations;
    }

    public function getSupportedTypes(): array
    {
        $types = [];
        foreach ($this->factories as $factory) {
            foreach (ProjectType::cases() as $type) {
                foreach (Kpi::cases() as $kpi) {
                    if ($factory->supports($type, $kpi)) {
                        $types[$type->value][] = $kpi->value;
                    }
                }
            }
        }
        return $types;
    }
}
