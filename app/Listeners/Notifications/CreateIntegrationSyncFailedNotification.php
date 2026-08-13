<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\IntegrationSyncFailed;
use App\Models\Agency;
use App\Models\Project;
use App\Services\NotificationService;
use App\Services\Notifications\ProjectNotificationRecipientResolver;
use Illuminate\Support\Carbon;

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

        $error = filled($e->error) ? $e->error : 'Ошибка съёма данных';
        $now = Carbon::now($this->agencyTimezone());
        $text = $error.', '.$now->format('H:i').', '.$now->format('d.m.y').', [[proj]]';

        $links = [[
            'key' => 'proj',
            'label' => $projectName,
            'route' => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product' => 'integrations',
            'category' => 'important',
            'project' => $projectName,
            'collector' => $e->collector,
            'inline_meta' => true,
        ];

        foreach ($this->recipients->userIdsForProject($e->projectId) as $userId) {
            $this->svc->create(
                userId: $userId,
                text: $text,
                linkUrl: null,
                links: $links,
                type: IntegrationSyncFailed::TYPE,
                payload: $payload,
                projectId: $e->projectId,
            );
        }
    }

    private function agencyTimezone(): string
    {
        $timezone = Agency::query()->orderBy('id')->value('time_zone');

        if (filled($timezone)) {
            return (string) $timezone;
        }

        return (string) config('app.timezone', 'UTC');
    }
}
