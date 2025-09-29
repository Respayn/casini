<?php

namespace App\Events\Notifications;

use Carbon\Carbon;

class ChannelsAnalyticsStopped
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $channelId,
        public string $channelName,
        public string $system,
        public ?Carbon $lastSeenAt = null,
    ) {}
}
