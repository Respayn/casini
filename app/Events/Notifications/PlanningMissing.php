<?php

namespace App\Events\Notifications;

class PlanningMissing
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $channelId,
        public string $channelName,
    ) {}
}
