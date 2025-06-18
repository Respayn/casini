<?php

namespace App\Data\YandexMetrika;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class GoalDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public float $defaultPrice,
        public bool $isRetargeting,
        public string $goalSource,
        public bool $isFavorite,
        public string $status,
        public int $depth,
    ) {
    }
}
