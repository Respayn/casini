<?php

namespace App\Events\Notifications;

class PlanningApprovalRequired
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $channelId,
        public string $channelName,
    ) {}
}
