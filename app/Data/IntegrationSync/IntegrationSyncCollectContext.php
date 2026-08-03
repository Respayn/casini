<?php

namespace App\Data\IntegrationSync;

use Illuminate\Support\Carbon;

readonly class IntegrationSyncCollectContext
{
    public function __construct(
        public int $projectId,
        public Carbon $targetDate,
    ) {}
}
