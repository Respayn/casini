<?php

namespace App\Events\Notifications;

class ChannelManagerChanged
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $channelId,
        public string $channelName,
        public ?string $oldManager,
        public string $newManager,
    ) {}
}
