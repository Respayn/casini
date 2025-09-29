<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\ChannelsAnalyticsStopped;
use App\Services\NotificationService;

class CreateChannelsAnalyticsStoppedNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(ChannelsAnalyticsStopped $e): void
    {
        $text = "Перестали поступать данные из {$e->system} в канале [[chan]]";

        $links = [[
            'key'    => 'chan',
            'label'  => $e->channelName,
            'route'  => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product'      => 'channels',
            'category'     => 'important',
            'project'      => $e->projectName,
            'channel_id'   => $e->channelId,
            'channel_name' => $e->channelName,
            'system'       => $e->system,
            'last_seen_at' => optional($e->lastSeenAt)->toIso8601String(),
        ];

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            links:     $links,
            type:      'channels.analytics.stopped',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
