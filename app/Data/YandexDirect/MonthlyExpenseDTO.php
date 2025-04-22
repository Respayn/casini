<?php

namespace App\Data\YandexDirect;

use Illuminate\Support\Carbon;

class MonthlyExpenseDTO
{
    public function __construct(
        public readonly Carbon $month,
        public readonly float $cost
    ) {
    }

    public function toArray(): array
    {
        return [
            'month' => $this->month->format('Y-m'),
            'cost' => $this->cost
        ];
    }
}
