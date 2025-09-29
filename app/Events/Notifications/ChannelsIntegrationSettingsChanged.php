<?php

namespace App\Events\Notifications;

class ChannelsIntegrationSettingsChanged
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $channelId,
        public string $channelName,
        public string $instrument,
        /** @var string[] */
        public array $changedKeys = [],
    ) {}
}
