<?php

namespace App\Events\Notifications;

class ChannelsBonusCalculated
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $projectName,
        public int $channelId,
        public string $channelName,
        public string $month,      // '2025-09' либо 'Сентябрь 2025'
        public float $amount,
    ) {}
}
