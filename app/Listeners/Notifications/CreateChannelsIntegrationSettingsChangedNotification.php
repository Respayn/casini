<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\ChannelsIntegrationSettingsChanged;
use App\Services\NotificationService;

class CreateChannelsIntegrationSettingsChangedNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(ChannelsIntegrationSettingsChanged $e): void
    {
        $text = "Изменились настройки интеграции ({$e->instrument}) в канале [[chan]]";

        $links = [[
            'key'    => 'chan',
            'label'  => $e->channelName,
            'route'  => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product'       => 'channels',
            'category'      => 'other',
            'project'       => $e->projectName,
            'channel_id'    => $e->channelId,
            'channel_name'  => $e->channelName,
            'instrument'    => $e->instrument,
            'changed_keys'  => array_values($e->changedKeys),
        ];

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            links:     $links,
            type:      'channels.integration.settings.changed',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
