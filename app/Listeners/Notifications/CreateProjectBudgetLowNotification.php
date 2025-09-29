<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\ProjectBudgetLow;
use App\Services\NotificationService;

class CreateProjectBudgetLowNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(ProjectBudgetLow $e): void
    {
        $text = 'Низкий остаток бюджета по [[proj]]';

        $links = [[
            'key'    => 'proj',
            'label'  => $e->projectName,
            'route'  => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product'     => 'channels',
            'category'    => 'important',
            'project'     => $e->projectName,
            'remaining'   => $e->remaining,
            'instrument'  => $e->instrument,
            'channel_id'  => $e->channelId,
        ];

        $this->svc->create(
            userId:     $e->userId,
            text:       $text,
            linkUrl:    null,
            links:      $links,
            type:       'channels.budget.low',
            payload:    $payload,
            projectId:  $e->projectId,
        );
    }
}
