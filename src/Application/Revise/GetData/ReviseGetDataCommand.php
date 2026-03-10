<?php

namespace Src\Application\Revise\GetData;

use Carbon\Carbon;

class ReviseGetDataCommand
{
    public function __construct(
        public readonly Carbon $dateFrom,
        public readonly Carbon $dateTo,
        public readonly ?int $clientId = null,
        public readonly ?int $managerId = null,
        public readonly bool $fetchFromDirect = false,
        public readonly ?int $channelId = null
    ) {}
}
