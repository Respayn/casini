<?php

namespace App\Services\Agency;

use App\Models\Agency;
use RuntimeException;

/**
 * Номер агентства виден в интерфейсе: случайный, чтобы по нему нельзя было
 * посчитать агентства и перебирать их по порядку.
 */
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

        $taken = Agency::query()
            ->whereBetween('id', [self::MIN, self::MAX])
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(int) $id => true]);

        for ($id = self::MIN; $id <= self::MAX; $id++) {
            if (! $taken->has($id)) {
                return $id;
            }
        }

        throw new RuntimeException('Свободных 4-значных ID агентства не осталось');
    }
}
