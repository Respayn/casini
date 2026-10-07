<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\IntegrationSyncFailed;
use App\Models\Project;
use App\Services\NotificationService;
use App\Services\Notifications\ProjectNotificationRecipientResolver;
use App\Support\SafeLogger;

class CreateIntegrationSyncFailedNotification
{
    public function __construct(
        private NotificationService $svc,
        private ProjectNotificationRecipientResolver $recipients,
    ) {}

    public function handle(IntegrationSyncFailed $e): void
    {
        $project = Project::query()->find($e->projectId);
        $projectName = filled($project?->name)
            ? (string) $project->name
            : 'Клиенто-проект №'.$e->projectId;

        $rawError = filled($e->error) ? (string) $e->error : 'Ошибка съёма данных';
        $text = SafeLogger::forDisplay($rawError);

        $payload = [
            'product' => 'integrations',
            'category' => 'important',
            'project' => $projectName,
            'collector' => $e->collector,
            'error_detail' => $rawError,
        ];

        foreach ($this->recipients->userIdsForProject($e->projectId) as $userId) {
            $this->svc->create(
                userId: $userId,
                text: $text,
                linkUrl: null,
                links: [],
                type: IntegrationSyncFailed::TYPE,
                payload: $payload,
                projectId: $e->projectId,
            );
        }
    }
}
