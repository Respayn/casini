<?php

namespace App\Events\Notifications;

use Carbon\Carbon;

class FundsReceived
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $clientId,
        public string $clientName,
        public float $amount,
        public ?string $docNo = null,
        public ?Carbon $postedAt = null
    ) {}
}
