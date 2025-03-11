<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BonusConditionData extends Data
{
    public bool $bonusesEnabled = false;
    public bool $calculateInPercentage = false;
    public ?float $clientPayment = null;
    public int $startMonth = 1; // Значение по умолчанию

    /** @var BonusIntervalData[] $intervals */
    public array $intervals = [];

    public function __construct(array $data)
    {
        $this->bonusesEnabled = $data['bonusesEnabled'] ?? false;
        $this->calculateInPercentage = $data['calculateInPercentage'] ?? false;
        $this->clientPayment = $data['clientPayment'] ?? null;
        $this->startMonth = $data['startMonth'] ?? 1;
        $this->intervals = array_map(fn($interval) => new BonusIntervalData($interval), $data['intervals'] ?? []);
    }
}
