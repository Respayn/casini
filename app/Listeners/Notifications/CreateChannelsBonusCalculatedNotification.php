<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\ChannelsBonusCalculated;
use App\Services\NotificationService;

class CreateChannelsBonusCalculatedNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(ChannelsBonusCalculated $e): void
    {
        $text = "Были рассчитаны бонусы за {$e->month} в канале [[chan]]";

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
            'month'        => $e->month,
            'amount'       => $e->amount,
        ];

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            links:     $links,
            type:      'channels.bonus.calculated',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
