<?php

namespace Src\Planning\Domain\ValueObjects;

use Src\Domain\ValueObjects\Quarter;

class QuarterApproval
{
    private Quarter $quarter;
    private bool $approved;
    private ?string $approvedAt;

    public function __construct(Quarter $quarter, bool $approved, ?string $approvedAt = null)
    {
        $this->quarter = $quarter;
        $this->approved = $approved;
        $this->approvedAt = $approved && $approvedAt ? $approvedAt : null;
    }

    public function getQuarter(): Quarter
    {
        return $this->quarter;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * Дата согласования в формате Y-m-d.
     */
    public function getApprovedAt(): ?string
    {
        return $this->approvedAt;
    }
}
