<?php

namespace Src\Planning\Domain\Factories;

use Src\Planning\Domain\ValueObjects\KpiParametersSchema;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;

interface KpiParametersSchemaFactoryInterface
{
    public function create(ProjectType $type, Kpi $kpi): KpiParametersSchema;
    public function supports(ProjectType $type, Kpi $kpi): bool;
}