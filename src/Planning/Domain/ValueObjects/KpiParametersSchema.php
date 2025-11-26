<?php

namespace Src\Planning\Domain\ValueObjects;

use Src\Shared\ValueObjects\KpiParameter;

final class KpiParametersSchema
{
    /** @var KpiParameter[] */
    private array $parameters = [];

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /** @return KpiParameter[] */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getPrimaryParameter(): ?KpiParameter
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->isPrimary()) {
                return $parameter;
            }
        }
        return null;
    }

    public function getCalculatedParameters(): array
    {
        return array_filter($this->parameters, fn($param) => $param->isCalculated());
    }

    public function getSimpleParameters(): array
    {
        return array_filter($this->parameters, fn($param) => !$param->isCalculated());
    }
}
