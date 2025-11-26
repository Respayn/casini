<?php

namespace Src\Planning\Domain\ValueObjects;

use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\KpiParameter;
use Src\Shared\ValueObjects\ProjectType;

class KpiParametersSchemaBuilder
{
    private ProjectType $projectType;
    private Kpi $kpi;
    private array $parameters = [];

    public function __construct(ProjectType $projectType, Kpi $kpi)
    {
        $this->projectType = $projectType;
        $this->kpi = $kpi;
    }

    public function addParameter(
        string $id,
        string $label,
        ?string $format = null,
        bool $isCalculated = false,
        ?string $formula = null,
        array $dependencies = [],
        bool $isPrimary = false
    ): self {
        $this->parameters[] = new KpiParameter(
            $id,
            $label,
            $format,
            $isCalculated,
            $formula,
            $dependencies,
            $isPrimary
        );

        return $this;
    }

    public function addCalculatedParameter(
        string $id,
        string $label,
        string $formula,
        array $dependencies,
        ?string $format = null,
        bool $isPrimary = false
    ): self {
        return $this->addParameter(
            $id,
            $label,
            $format,
            true,
            $formula,
            $dependencies,
            $isPrimary
        );
    }

    public function addSimpleParameter(
        string $id,
        string $label,
        ?string $format = null,
        bool $isPrimary = false
    ): self 
    {
        return $this->addParameter(
            $id,
            $label,
            $format,
            false,
            null,
            [],
            $isPrimary
        );
    }

    public function build(): KpiParametersSchema
    {
        $this->validate();
        return new KpiParametersSchema($this->parameters);
    }

    private function validate(): void
    {
        if (empty($this->parameters)) {
            throw new \InvalidArgumentException(
                'Schema must contain at least one parameter'
            );
        }

        $primaryCount = array_filter($this->parameters, fn($param) => $param->isPrimary());
        if (count($primaryCount) > 1) {
            throw new \InvalidArgumentException(
                'Schema can have only one primary parameter'
            );
        }

        foreach ($this->parameters as $param) {
            if ($param->isCalculated() && !$param->getFormula()) {
                throw new \InvalidArgumentException(
                    "Calculated parameter {$param->getId()} must have a formula"
                );
            }
        }
    }
}
