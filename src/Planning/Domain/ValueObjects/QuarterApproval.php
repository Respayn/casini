<?php

namespace Src\Planning\Domain\ValueObjects;

use Src\Shared\ValueObjects\Quarter;

class QuarterApproval
{
    private Quarter $quarter;
    private bool $approved;

    public function __construct(Quarter $quarter, bool $approved)
    {
        $this->quarter = $quarter;
        $this->approved = $approved;
    }

    public function getQuarter(): Quarter
    {
        return $this->quarter;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }
}