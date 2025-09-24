<?php

namespace App\Events\Notifications;

use Carbon\Carbon;

class ChannelsInstrumentStopped
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $channelId,
        public string $channelName,
        public string $instrument,
        public ?Carbon $lastSeenAt = null,
    ) {}
}
