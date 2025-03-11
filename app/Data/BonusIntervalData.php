<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BonusIntervalData extends Data
{
    public float $fromPercentage;
    public float $toPercentage;
    public ?float $bonusAmount = null;
    public ?float $bonusPercentage = null;

    public function __construct(array $data)
    {
        $this->fromPercentage = $data['fromPercentage'];
        $this->toPercentage = $data['toPercentage'];
        $this->bonusAmount = $data['bonusAmount'] ?? null;
        $this->bonusPercentage = $data['bonusPercentage'] ?? null;
    }
}
