<?php

namespace Src\Planning\Domain\Factories;

use Src\Planning\Domain\ValueObjects\KpiParametersSchema;
use Src\Planning\Domain\ValueObjects\KpiParametersSchemaBuilder;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;

abstract class AbstractKpiParametersSchemaFactory implements KpiParametersSchemaFactoryInterface
{
    protected ProjectType $projectType;
    protected Kpi $kpi;

    protected function createSchema(ProjectType $type, Kpi $kpi): KpiParametersSchema
    {
        $this->projectType = $type;
        $this->kpi = $kpi;

        $builder = new KpiParametersSchemaBuilder($type, $kpi);
        $this->configureParameters($builder);
        return $builder->build();
    }

    abstract protected function configureParameters(KpiParametersSchemaBuilder $builder);

    public function create(ProjectType $type, Kpi $kpi): KpiParametersSchema
    {
        if (!$this->supports($type, $kpi)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Factory %s does not support %s with %s',
                    static::class,
                    $type->value,
                    $kpi->value
                )
            );
        }

        return $this->createSchema($type, $kpi);
    }

    abstract public function supports(ProjectType $type, Kpi $kpi): bool;
}
