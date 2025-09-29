<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\PlanningApprovalRequired;
use App\Services\NotificationService;

class CreatePlanningApprovalRequiredNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(PlanningApprovalRequired $e): void
    {
        $text = "Нужно согласовать план в [[chan]]";

        $links = [[
            'key'    => 'chan',
            'label'  => $e->channelName,
            'route'  => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product'      => 'planning',
            'category'     => 'other',
            'project'      => $e->projectName,
            'channel_id'   => $e->channelId,
            'channel_name' => $e->channelName,
        ];

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            links:     $links,
            type:      'planning.approval.required',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
