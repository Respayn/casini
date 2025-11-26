<?php

namespace Src\Shared\ValueObjects;

class KpiParameter
{
    private string $id;
    private string $label;
    private ?string $format;
    private bool $isCalculated;

    private ?string $formula;
    private array $dependencies;
    private bool $isPrimary;

    public function __construct(
        string $id,
        string $label,
        ?string $format = null,
        bool $isCalculated = false,
        ?string $formula = null,
        array $dependencies = [],
        bool $isPrimary = false
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->format = $format;
        $this->isCalculated = $isCalculated;
        $this->formula = $formula;
        $this->dependencies = $dependencies;
        $this->isPrimary = $isPrimary;
    }

    public function equals(KpiParameter $other): bool
    {
        return $this->id === $other->id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function isCalculated(): bool
    {
        return $this->isCalculated;
    }

    public function getFormula(): ?string
    {
        return $this->formula;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }
}
