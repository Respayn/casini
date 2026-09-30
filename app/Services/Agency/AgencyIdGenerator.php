<?php

namespace App\Services\Agency;

use App\Models\Agency;
use RuntimeException;

class AgencyIdGenerator
{
    public const MIN = 1000;

    public const MAX = 9999;

    private const MAX_ATTEMPTS = 50;

    public function generate(): int
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $id = random_int(self::MIN, self::MAX);

            if (! Agency::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        throw new RuntimeException(
            'Не удалось подобрать свободный 4-значный ID агентства после '.self::MAX_ATTEMPTS.' попыток'
        );
    }
}
