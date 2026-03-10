<?php

namespace Src\Domain\Revise;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ReviseRepositoryInterface
{
    public function GetData(
        Carbon $periodFrom,
        Carbon $periodTo,
        ?int $clientId = null,
        ?int $managerId = null,
        bool $fetchFromDirect = false,
        ?int $channelId = null
    ): Collection;
}
