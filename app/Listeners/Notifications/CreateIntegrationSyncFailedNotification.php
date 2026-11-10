<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\IntegrationSyncFailed;
use App\Models\Notification;
use App\Models\Project;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\Notifications\ProjectNotificationRecipientResolver;
use App\Services\NotificationService;
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
        $isAccessError = SafeLogger::isIntegrationAccessError($rawError);
        $integrationCode = IntegrationManualRefreshProgress::accessIntegrationCode($e->collector);
        $integrationName = IntegrationManualRefreshProgress::accessIntegrationName($e->collector);

        $text = $isAccessError
            ? 'Доступ к '.$integrationName.' больше не действует. Подключите его заново.'
            : SafeLogger::forDisplay($rawError);

        $payload = [
            'product' => 'integrations',
            'category' => 'important',
            'project' => $projectName,
            'collector' => $e->collector,
            'error_detail' => $rawError,
        ];

        if ($isAccessError) {
            $payload['access_error'] = true;
            $payload['integration_code'] = $integrationCode;
            $payload['integration'] = $integrationName;
        }

        foreach ($this->recipients->userIdsForProject($e->projectId) as $userId) {
            if ($isAccessError && $this->hasUnreadAccessError($userId, $e->projectId, $integrationCode)) {
                continue;
            }

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

    private function hasUnreadAccessError(int $userId, int $projectId, string $integrationCode): bool
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->where('type', IntegrationSyncFailed::TYPE)
            ->whereNull('read_at')
            ->where('payload->access_error', true)
            ->where('payload->integration_code', $integrationCode)
            ->exists();
    }
}
