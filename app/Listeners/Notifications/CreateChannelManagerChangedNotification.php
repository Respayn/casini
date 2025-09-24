<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\ChannelManagerChanged;
use App\Services\NotificationService;

class CreateChannelManagerChangedNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(ChannelManagerChanged $e): void
    {
        $text = "Изменился менеджер в канале [[chan]]";

        $links = [[
            'key'    => 'chan',
            'label'  => $e->channelName,
            'route'  => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product'      => 'channels',
            'category'     => 'other',
            'project'      => $e->projectName,
            'channel_id'   => $e->channelId,
            'channel_name' => $e->channelName,
            'old_manager'  => $e->oldManager,
            'new_manager'  => $e->newManager,
        ];

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            links:     $links,
            type:      'channels.manager.changed',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
