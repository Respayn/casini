<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\PlanningMissing;
use App\Services\NotificationService;

class CreatePlanningMissingNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(PlanningMissing $e): void
    {
        $text = "Нет плана в [[chan]]";

        $links = [[
            'key'    => 'chan',
            'label'  => $e->channelName,
            'route'  => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product'      => 'planning',
            'category'     => 'important',
            'project'      => $e->projectName,
            'channel_id'   => $e->channelId,
            'channel_name' => $e->channelName,
        ];

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            links:     $links,
            type:      'planning.missing',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
